<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 * Transfer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 *
 * @method     TBT_Rewards_Model_Mysql4_Transfer_Collection getCollection()
 */
class TBT_Rewards_Model_Transfer extends TBT_Rewards_Model_Abstract
{
    /** Properties for event transfers **/
    protected $_eventPrefix = 'rewards_transfer';
    protected $_eventObject = 'rewards_transfer';

    protected $_customer = null;
    protected $_isNew    = false;
    
    const CACHE_TAG = 'rewards_transfer';
    
    /**
     * Model cache tag for clear cache in after save and after delete
     *
     * When you use true - all cache will be clean
     *
     * @var string || true
     */
    protected $_cacheTag    = self::CACHE_TAG;

    /**
     * We use $_isResave to skip re-running some unnecesary code in _beforeSave, _afterSave and _afterLoad methods.
     *
     * @var bool
     **/
    protected $_isResave = false;

    public function _construct() {
        parent::_construct ();
        $this->_init ( 'rewards/transfer' );
    }

    /**
     * Cancels this points tranfer if possible.
     * If success, saves the cancellation.
     * @return true if success, false otherwise.
     * @deprecated
     */
    public function cancel() {
        $status_change_result = $this->setStatusId($this->getStatusId(), TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED);

        if($status_change_result === false) {
            return false;
        }

        $this->save();

        return true;
    }
    
    /**
     * DEPRECATED
     * @use setReviewId()
     */
    public function setRatingId($id) {
        return $this->setReviewId($id);
    }

    public function setOrderId($id) {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('order'));
        $this->setReferenceId ( $id );

        return $this;
    }

    public function setPollId($id) {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('poll'));
        $this->setReferenceId ( $id );

        return $this;
    }

    public function setAsSignup() {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('signup'));
        return $this;
    }

    public function isOrder() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('order'));
    }

    public function isPoll() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('poll'));
    }

    /**
     * True if transfer references transfer
     * @return boolean
     */
    public function isTransfer() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('revoke'));
    }

    /**
     * Is this any kind of friend-to-friend transfer?
     *
     * @return boolean
     */
    public function isFriendTransfer() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('send_friend'));
    }

    public function getTransfersAssociatedWithOrder($order_id) {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('order'))
            ->addFieldToFilter('reference_id', $order_id);
    }
    
    public function getTransfersAssociatedWithTransfer($transfer_id) {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('revoke'))
            ->addFieldToFilter('reference_id', $transfer_id);
    }

    /**
     * Sets the status of the transfer if possible.
     * If the new transfer is illegal, returns false,
     * otherwise, updates the status and returns $this.
     * 
     * @return mixed boolean if false, or $this if OK
     */
    public function setStatusId($oldStatusId, $newStatusId) {
        $availStatuses = Mage::getSingleton ( 'rewards/transfer_status' )->getAvailableNextStatuses ( $this->getStatusId () );
        
        if (array_search ( $newStatusId, $availStatuses ) !== false) {
            return $this->setData ( "status_id", $newStatusId );
        } else {
            return false;
        }
    }

    /**
     * Will set the customer model for this transfer.
     *
     * @param TBT_Rewards_Model_Customer|Mage_Customer_Model_Customer $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    public function _beforeSave()
    {
        $now = Mage::getModel('core/date')->gmtDate();
        
        // automatically set the last updated timestamps to the current date/time
        if (!$this->getSkipUpdatedAt()) {
            $this->setUpdatedAt ($now);
        }

        if ($this->_isResave) {
            return parent::_beforeSave();
        }

        if ($this->getQuantity () == 0) {
            throw new Exception ( "You must select a quantity of points not equal to (0)." . " If you want to void a transfer, set it's status to cancelled or revoked." );
        }

        if ($this->_customer && $this->_customer->getId()) {
            $customer = $this->_customer;
            $this->setCustomerId($customer->getId());
        }

        if ($this->getCustomerId () == null || $this->getCustomerId () == '') {
            throw new Exception ( "Please select a customer for this transfer." );
        }

        if (!empty($customer) && $customer instanceof TBT_Rewards_Model_Customer) {
            if (!$customer->isPointsBalanceLoaded()) $customer->loadPointsBalances();

        } else {
            $customer = Mage::getModel ( 'rewards/customer' )->load ( $this->getCustomerId () );
            if (!$customer || !$customer->getId()) {
                throw new Exception ( "Transfer could not be completed because customer no longer exists!" );
            }
        }

        $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        $currentPointsBalance = $customer->getUsablePointsBalance($defaultCurrencyId);
        if (!$this->getId()) {
            $currentPointsBalance += $this->getQuantity();
        }
        
        if ($currentPointsBalance < 0) {
            if (Mage::helper('rewards/config')->canHaveNegativePtsBalance()) {
                /* warning, going into negative points!! */
            } else {
                throw new Exception ( "The transfer cannot be completed because the customer will have less than zero (0) points." );
            }
        }

        if (!$this->_customer && $customer instanceof TBT_Rewards_Model_Customer) {
            // Clean the garbage only if we created it
            $customer->clearInstance();
        }

        if ($this->getStatusId () == - 1) {
            $this->setReferenceId ( $this->getId () )
                ->setStatusId ( null, TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED )
                ->setReasonId ( Mage::helper('rewards/transfer_reason')->getReasonId('revoke') )
                ->setQuantity ( $this->getQuantity () * - 1 )
                ->setId ( null );
        }

        if ($this->getId ()) {
            $this->_isNew = false;

            $s = Mage::getSingleton ( 'rewards/transfer_status' );

            if (! $this->getStatusId ()) {
                $this->setStatusId ( null, $this->getOrigData('status') );
            }

            $current_status = $this->getOrigData('status_id');
            $next_status = $this->getStatusId ();

            $availStat = $s->getAvailStatuses ( $current_status );
            if (! isset ( $availStat [$next_status] )) {
                throw new Exception ( "You cannot change the status from " . $s->getStatusCaption ( $current_status ) . " to " . $s->getStatusCaption ( $next_status ) . " for this transfer." );
            }

            try {
                if (! $s->canAdjustQty ( $current_status ) && ($this->getOrigData('quantity') != $this->getQuantity ())) {
                    throw new Exception ( "quantity" );
                }
                if (! $s->canAdjustComments ( $current_status ) && ($this->getOrigData('comments') != $this->getComments ())) {
                    throw new Exception ( "comments" );
                }
                if (! $s->canAdjustCustomer ( $current_status ) && ($this->getOrigData('customer_id') != $this->getCustomerId ())) {
                    throw new Exception ( "customer" );
                }
                if (! $s->canAdjustReference ( $current_status ) && ($this->getOrigData('reference_id') != $this->getReferenceId ())) {
                    throw new Exception ( "associated reference" );
                }
            } catch ( Exception $e ) {
                $attr = $e->getMessage ();
                throw new Exception ( "You cannot change the $attr for this transfer because of the " . "current status that it is in.  Instead, make a new transfer as an adjustment." );
            }
            if (! $s->canAdjustStatus ( $current_status, $next_status )) {
                throw new Exception ( "You cannot change the status from $current_status to $next_status." );
            }

        } else {
            $this->_isNew = true;

            // if this is a new transfer then automatically set the created timestamps to the current date/time
            $created_at = $this->getCreatedAt ();
            if (empty ( $created_at )) {
                $this->setCreatedAt ($now);
            }

            //get On-Hold initial status override
            if ($this->getRuleId() && $this->getReasonId() != Mage::helper('rewards/transfer_reason')->getReasonId('order')) {
                $rule = Mage::getModel('rewards/special')->load($this->getRuleId());
                if ($rule->getOnholdDuration() > 0) {
                    $this->setEffectiveStart(date('Y-m-d H:i:s', strtotime("+{$rule->getOnholdDuration()} days")));
                    $this->setData('status_id', TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME);
                }
            }
            
            // Set the effective start date (if it wasn't already set)
            $effectiveStart = $this->getEffectiveStart();
            if (empty($effectiveStart)) {
                $this->setEffectiveStart($now);
            }
        }


        return parent::_beforeSave ();
    }

    public function _afterSave()
    {
        if ($this->_isResave) {
            return parent::_afterSave();
        }

        if ($this->_isNew) {
            // set _isResave flag
            $this->_isResave = true;
        }

        if ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('revoke')) {
            Mage::dispatchEvent('rewards_transfer_revoke', array(
                'rewards_transfer' => Mage::getModel('rewards/transfer')->load($this->getReferenceId()),
                'new_transfer' => $this
            ));
        }

        return parent::_afterSave ();
    }

    public function _afterLoad()
    {
        if ($this->_isResave) {
            // reset isResave flag
            $this->_isResave = false;
        }

        return parent::_afterLoad();
    }

    public function _beforeDelete() {
        throw new Exception ( "You cannot delete a transfer. You may however cancel or revoke an existing transfer to achieve the same effect." );
        return parent::_beforeSave ();
    }

    /**
     * Initiates a transfer model based on given criteria and verifies usage.
     *
     * @param integer $num_points
     * @param integer $currency_id
     * @param integer $rule_id
     * @return TBT_Rewards_Model_Transfer
     */
    public function initTransfer($num_points, $rule_id, $customerId = null, $skipChecks = false)
    {
        if (!Mage::getSingleton('rewards/session')->isCustomerLoggedIn() && !$skipChecks
            && !Mage::getSingleton ( 'rewards/session' )->isAdminMode ()) {
            return null;
        }

        // ALWAYS ensure that we only give an integral amount of points
        $num_points = floor ( $num_points );

        if ($num_points == 0) {
            return null;
        }

        /**
         * the transfer model to work with is this model (because this function was originally from the transfer helper)
         * @var TBT_Rewards_Model_Transfer
         */
        $transfer = &$this;

        if ($num_points <= 0) {
            $customerId = $customerId ? $customerId : Mage::getSingleton('customer/session')->getCustomerId();
            $customer = Mage::getModel ( 'rewards/customer' )->load ( $customerId );
            $currency_id = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            if (($customer->getUsablePointsBalance ( $currency_id ) + $num_points) < 0) {
                $points_balance_str = Mage::getModel ( 'rewards/points' )->set ( $currency_id, $customer->getUsablePointsBalance ( $currency_id ) );
                $req_points_str = Mage::getModel ( 'rewards/points' )->set ( $currency_id, $num_points * - 1 );
                $error = Mage::helper('rewards')->__ ( 'Not enough points for transaction. You have %s, but you need %s.', $points_balance_str, $req_points_str );
                throw new Exception ( $error );
            }
        }
        
        $transfer->setId(null)
            ->setQuantity($num_points)
            ->setCustomerId($customerId)
            ->setRuleId($rule_id);

        return $transfer;
    }

    /**
     * Revokes a points transfer by creating an oposite, linked points transfer.
     * @throws Exception
     * @return TBT_Rewards_Model_Transfer the resulting REVOKED reason type points transfer
     */
    public function revoke() {
        $transfer = Mage::getModel('rewards/transfer');

        // get the default starting status - usually Pending
        if ( ! $transfer->setStatusId(null, TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED) ) {
            // we tried to use an invalid status... is getInitialTransferStatusAfterReview() improper ??
            return $this;
        }

        $customer_id = $this->getCustomerId();
        $num_points = $this->getQuantity() * (-1);
        $currency_id = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        
        $customer = Mage::getModel('rewards/customer')->load($customer_id);
        if ( ($customer->getUsablePointsBalance($currency_id) + $num_points) < 0 ) {
            $error = Mage::helper('rewards')->__('Not enough points for transaction. You have %s, but you need %s',
            Mage::getModel('rewards/points')->set($currency_id, $customer->getUsablePointsBalance($currency_id)),
            Mage::getModel('rewards/points')->set($currency_id, $num_points * - 1));
            throw new Exception($error);
        }

        $comments = Mage::getStoreConfig('rewards/transferComments/revoked');
        $comments = str_replace('\n', "\n", $comments);
        $comments = Mage::helper('rewards')->__($comments, $this->getComments());

        $transfer->setId(null)
            ->setQuantity($num_points)
            ->setCustomerId($customer_id)
            ->setReferenceId($this->getId())
            ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('revoke'))
            ->setComments($comments)
            ->save();

        return $transfer;
    }

    /**
     * Setter for the transfer '_isResave' flag. This is currently used only for admin mass points assignment.
     *
     * @param bool $flag
     * @return $this
     */
    public function setIsResave($flag)
    {
        $this->_isResave = $flag;
        return $this;
    }

    /**
     * Clearing object's data
     *
     * @return $this
     */
    protected function _clearData()
    {
        $this->_customer = null;
        $this->_isNew    = false;
        $this->_isResave = false;
        $this->setData(array());
        $this->setOrigData();

        return $this;
    }

    public function setReviewId($id) {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('product_review'));
        $this->setReferenceId ( $id );

        return $this;
    }
    
    public function setTagId($id) {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('tag'));
        $this->setReferenceId ( $id );

        return $this;
    }
    
    public function setNewsletterId($id) {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'));
        $this->setReferenceId ( $id );

        return $this;
    }

    public function isNewsletter() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'));
    }

    public function isTag() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('tag'));
    }

    public function isReview() {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('product_review'));
    }
}
