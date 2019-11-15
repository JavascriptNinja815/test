<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time Sweet Tooth spent
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension.
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Handler for points expiry process.
 * @nelkaake 31/01/2010 11:09:01 PM
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Expiry extends Varien_Object
{
    const SECS_IN_DAY = 86400;
    const SECS_IN_HOUR = 3600;
    const SECS_IN_MIN = 60;
    protected $_pointsIndexAvailable = null;
    protected $_lastPointsActivityCache = array();

    /**
     * Checks all customers for points balance expires.  IF points have expired,
     * their points balance will be nullified.
     *
     * @return $this
     */
    public function checkAllCustomers()
    {
        $pageSize = 1000;
        $iterator = Mage::getSingleton('core/resource_iterator');
        $extraFields = array('firstname', 'lastname', 'middlename', 'prefix', 'suffix', 'rewards_points_notification');
        $customers = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect($extraFields, 'left');
        
        if ($this->_isPointsIndexAvailable()) {
            Mage::getResourceModel('rewards/customer_indexer_points_collection')
                ->joinPointsBalance($customers);
        }

        $customers->setPageSize($pageSize);
        $pages = $customers->getLastPageNumber();
        $currentPage = 1;
        do {
            $select = $customers->getSelect();
            $select->limit($pageSize, ($currentPage - 1) * $pageSize);
            $iterator->walk($select, array(array($this, 'processCustomerData')));
            $currentPage++;
        } while ($currentPage <= $pages);

        return $this;
    }

    /**
     * Callback to iterator class to process customer data one by one
     * @see return must be set as empty array or null or '' otherwise core iterator will bring a recoverable error
     * @param array $walkData
     * @return null
     */
    public function processCustomerData($walkData)
    {
        $rewardsCustomer = Mage::getModel('rewards/customer')->setData($walkData['row']);
        $pointsBalance = Mage::getModel('rewards/customer_indexer_points');
        if ($this->_isPointsIndexAvailable()) {
            // We would have done a table join prior
            $pointsBalance->setData($walkData['row']);
            $rewardsCustomer->setIndexedPoints($pointsBalance);
        }
        if (!$rewardsCustomer->isPointsBalanceLoaded()) {
            $rewardsCustomer->loadPointsBalance();
        }

        $this->checkExpirePoints($rewardsCustomer);

        $pointsBalance->clearInstance();
        $rewardsCustomer->clearInstance();
    }

    /**
     * Checks if there are points to expire for each and every customer then
     * expires the points if need be.
     *
     * @param $rewardsCustomer TBT_Rewards_Model_Customer
     * @return $this
     */
    public function checkExpirePoints($rewardsCustomer)
    {
        if (! $rewardsCustomer->getId ()) {
            return $this;
        }

        if (! Mage::helper ( 'rewards/expiry' )->isEnabled ( $rewardsCustomer->getStoreId () )) {
            return $this;
        }

        $rewardsCustomer->loadPointsBalance();
        if ($rewardsCustomer->hasUsablePoints()) {
            $dateTimeHelper = $this->_getDateTimeHelper();
            $now = $dateTimeHelper->getZendDate();
            $expiryDate = $this->getExpiryDate($rewardsCustomer, false);
            
            if (!$expiryDate) {
                return $this;
            }
            
            if ($now->isLater($expiryDate)) {
                $oldBalance = $rewardsCustomer->getPointsSummary();
                $daysSinceExpiry = $this->getDaysSinceExpiry($rewardsCustomer);
                $this->expireCustomerPoints($rewardsCustomer);
                Mage::helper('rewards/expiry')->logExpiry($rewardsCustomer, $oldBalance, $daysSinceExpiry);

            } else {
                $daysUntilExpiry = $this->getDaysUntilExpiry($rewardsCustomer);
                $this->checkNotifications($rewardsCustomer, $daysUntilExpiry);
            }

            unset($this->_lastPointsActivityCache[$rewardsCustomer->getId()]);
        }

        return $this;
    }

    /**
     * Will expire the balance for the given customer
     * @param TBT_Rewards_Model_Customer $rewardsCustomer
     * @return $this
     */
    public function expireCustomerPoints($rewardsCustomer)
    {
        $usablePoints = $rewardsCustomer->getUsablePoints();
        $comments = Mage::helper ('rewards/expiry')->getExpiryMsg($rewardsCustomer->getStoreId());
        foreach ( $usablePoints as $currencyId => $pointsAmount ) {
            if ($pointsAmount <= 0) {
                continue;
            }

            $pointsAmount = (- 1) * floor($pointsAmount);
            $transfer = Mage::getModel('rewards/transfer_simple')
                ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('expire'))
                ->setStatusId(TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED)
                ->setCustomerId($rewardsCustomer->getId())
                ->setQuantity($pointsAmount)
                ->setComments($comments)
                ->save();

            if ($this->_isPointsIndexAvailable()) {
                $rewardsCustomer->getIndexedPoints()
                    ->setCustomerPointsUsable(0)
                    ->setCustomerPointsActive(0)
                    ->save();
            }

            $transfer->clearInstance();
        }

        return $this;
    }

    /**
     * Will return a locally cached copy of the original date of the customer's last approved points transaction
     *
     * @param TBT_Rewards_Model_Customer $rewardsCustomer
     * @param boolean $formatted (optional, default: true). Will return local formatted string if true.
     * @return null|Zend_Date|string
     */
    public function getLastPointsActivityDate($rewardsCustomer, $formatted = true)
    {
        $customerId = $rewardsCustomer->getId();
        if (!$customerId){
            return null;
        }

        if (!isset($this->_lastPointsActivityCache[$customerId])) {
            $this->_lastPointsActivityCache[$customerId] = $rewardsCustomer->getLatestActivityDate();
        }

        if (empty($this->_lastPointsActivityCache[$customerId])){
            return null;
        }

        $dateTimeHelper = $this->_getDateTimeHelper();
        $lastActivity = $dateTimeHelper->getZendDate($this->_lastPointsActivityCache[$customerId]);

        if ($formatted) {
            return Mage::helper('core')->formatDate($lastActivity);
        }

        return $lastActivity;
    }

    /**
     * Will return the Expiry Date for customer's balance
     *
     * @param TBT_Rewards_Model_Customer $rewardsCustomer
     * @param boolean $formatted (optional, default: true). Will return local formatted string if true.
     * @return null|Zend_Date|string
     */
    public function getExpiryDate($rewardsCustomer, $formatted = true)
    {
        if (!$rewardsCustomer->getId()) {
            return null;
        }

        if (!Mage::helper('rewards/expiry')->isEnabled($rewardsCustomer->getStoreId())) {
            return null;
        }

        if (!$rewardsCustomer->hasPoints()) {
            return null;
        }

        $lastActivity = $this->getLastPointsActivityDate($rewardsCustomer, false);
        $expiryDelay = Mage::helper('rewards/expiry')->getDelayDays($rewardsCustomer->getStoreId());
        
        if (!$lastActivity) {
            return null;
        }
        
        $expiryDate = $lastActivity->add($expiryDelay, Zend_Date::DAY);

        if ($formatted) {
            return Mage::helper('core')->formatDate($expiryDate);
        }

        return $expiryDate;
    }

    /**
     * Will return number of days until points expire
     *
     * @param TBT_Rewards_Model_Customer $rewardsCustomer
     * @return null|int
     */
    public function getDaysUntilExpiry($rewardsCustomer)
    {
        $dateTimeHelper = $this->_getDateTimeHelper();
        $now = $dateTimeHelper->getZendDate();
        $expiryDate = $this->getExpiryDate($rewardsCustomer, false);
        if ($expiryDate) {
            $seconds = $expiryDate->sub($now)->toValue();
            return $this->getNumDays ($seconds) + 1;
        }

        return null;
    }

    /**
     * Will return number of days since points have expired
     *
     * @param TBT_Rewards_Model_Customer $rewardsCustomer
     * @return null|int
     */
    public function getDaysSinceExpiry($rewardsCustomer)
    {
        $dateTimeHelper = $this->_getDateTimeHelper();
        $now = $dateTimeHelper->getZendDate();
        $expiryDate = $this->getExpiryDate($rewardsCustomer, false);
        if ($expiryDate) {
            $seconds = $now->sub($expiryDate)->toValue();
            return $this->getNumDays ($seconds) - 1;
        }

        return null;
    }

    /**
     * @deprecated
     *
     * Will return number of seconds between now and expiry date
     * @param TBT_Rewards_Model_Customer $rewardsCustomer
     * @return int|null seconds left until expiry or null if value can't be calculated
     */
    public function getTimeLeftToExpire($rewardsCustomer)
    {
        $dateTimeHelper = $this->_getDateTimeHelper();
        $now = $dateTimeHelper->getZendDate();
        $expiryDate = $this->getExpiryDate($rewardsCustomer, false);
        if ($expiryDate) {
            $seconds = $expiryDate->sub($now)->toValue();
            return $seconds;
        }

        return null;
    }

    /**
     * Checks to see if there are notifications that need to be sent.  If there are,
     * those notifications are setn and a log is written.
     *
     * @param TBT_Rewards_Model_Customer $c
     * @param int $expires_in_days
     */
    public function checkNotifications($c, $expires_in_days) {
        if ($expires_in_days == Mage::helper ( 'rewards/expiry' )->getWarning1Days ( $c->getStoreId () )) {
            $template = Mage::helper ( 'rewards/expiry' )->getWarning1EmailTemplate ( $c->getStoreId () );
            if ($template && $template !== 'none') {
                $expires_in_days = Mage::helper ( 'rewards/expiry' )->getWarning1Days ( $c->getStoreId () );
                $this->sendWarningEmail ( $c, $template, $expires_in_days );
                Mage::helper ( 'rewards/expiry' )->logExpiryNotification ( $c, $expires_in_days );
            }
        }
        if ($expires_in_days == Mage::helper ( 'rewards/expiry' )->getWarning2Days ( $c->getStoreId () )) {
            $template = Mage::helper ( 'rewards/expiry' )->getWarning2EmailTemplate ( $c->getStoreId () );
            if ($template && $template !== 'none') {
                $expires_in_days = Mage::helper ( 'rewards/expiry' )->getWarning2Days ( $c->getStoreId () );
                $this->sendWarningEmail ( $c, $template, $expires_in_days );
                Mage::helper ( 'rewards/expiry' )->logExpiryNotification ( $c, $expires_in_days );
            }
        }
    }

    /**
     * Sends a warning e-mail that points balance will expire in [$expires_in_days]
     * days t oa given customer
     *
     * @param TBT_Rewards_Model_Customer $parent
     * @param unknown_type $template
     * @param int $expires_in_days
     * @return boolean send successful?
     */
    public function sendWarningEmail($parent, $template, $expires_in_days) {
        $translate = Mage::getSingleton ( 'core/translate' ); /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline ( false );

        $emailHelper = Mage::helper('rewards/email');
        $sender = array ('name' => strip_tags ( Mage::helper ( 'rewards/expiry' )->getSenderName ( $parent->getStoreId () ) ), 'email' => strip_tags ( Mage::helper ( 'rewards/expiry' )->getSenderEmail ( $parent->getStoreId () ) ) );

        $vars = array(
            'customer_name' => $parent->getName(), 
            'customer_email' => $parent->getEmail(), 
            'store_name' => $parent->getStore()->getFrontendName(),
            'days_left' => $expires_in_days, 
            'points_balance' => ( string ) $parent->getPointsSummary()
        );
        
        $result = $emailHelper->sendTransactional($template, $sender, $parent, $vars);
        $translate->setTranslateInline ( true );

        return $result;
    }

    /**
     * Will convert seconds to days
     * @param int $seconds
     * @return float
     */
    public function getNumDays($seconds)
    {
        $days = floor ( $seconds / self::SECS_IN_DAY );
        return $days;
    }

    /**
     * @deprecated
     * @param $secs
     * @return array
     */
    public function getRemainingTime($secs) {
        $days = floor ( $secs / self::SECS_IN_DAY );
        $secs = $secs % self::SECS_IN_DAY;
        $hours = floor ( $secs / self::SECS_IN_HOUR );
        $secs = $secs % self::SECS_IN_HOUR;
        $minutes = floor ( $secs / self::SECS_IN_MIN );
        $secs = $secs % self::SECS_IN_MIN;
        return array ($days, $hours, $minutes, $secs );
    }

    /**
     * Will look up availability of Customer Points Index Table
     * @return boolean
     */
    protected function _isPointsIndexAvailable()
    {
        if (is_null($this->_pointsIndexAvailable)) {
            $this->_pointsIndexAvailable = Mage::helper('rewards/customer_points_index')->useIndex();
        }

        return $this->_pointsIndexAvailable;
    }

    /**
     * @return TBT_Rewards_Helper_Datetime
     */
    protected function _getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
    }

}
