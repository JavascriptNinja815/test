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
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Rewardssocial Action Resource Model
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_Mysql4_Action extends TBT_Rewards_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('rewardssocial2/action', 'id');
    }
    
    /**
     * Check if an entry exists for the received extra data 
     * (which can be a url, a product id, or something else)
     * 
     * @param Mage_Customer_Model_Customer $customer
     * @param string $action
     * @param string $extra
     * @return bool
     */
    public function wasAlreadyRewarded($customer, $action, $extra = null)
    {
        $actions = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('action', $action);
        
        if ($extra) {
            $actions->addFieldToFilter('extra', $extra);
        }
            
        return (bool)$actions->getSize();
    }
    
    /**
     * Check if there where more requests than the number of seconds
     * stored in the `rewards/rewardssocial2/request_interval` config setting
     * 
     * @param int $customerId
     * @return boolean
     */
    public function isRequestIntervalValid($customerId)
    {
        $requestInterval = Mage::getStoreConfig('rewards/rewardssocial2/request_interval');
        
        if (!$requestInterval) {
            return true;
        }
        
        $expression = new Zend_Db_Expr("DATE_SUB('". now() ."', INTERVAL {$requestInterval} SECOND)");
        
        $actions = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', array('gt' => $expression))
            ->getSize();
        
        return !$actions;
    }
    
    /**
     * Validate daily social request limits
     * 
     * @param int $customerId
     * @return bool
     */
    public function isWithinDailyLimit($customerId)
    {
        $dailyLimit = Mage::getStoreConfig('rewards/rewardssocial2/daily_limit');
        if (!$dailyLimit) {
            return true;
        }
        
        $datetime = Mage::helper('rewards/datetime')
            ->getZendDate(null, true)
            ->settime('0:00:00')
            ->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND);

        $requestsInThePastDay = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', array('gt' => $datetime))
            ->getSize();

        return ($requestsInThePastDay < $dailyLimit);
    }
    
    /**
     * Validate weekly social request limits
     * 
     * @param int $customerId
     * @return bool
     */
    public function isWithinWeeklyLimit($customerId)
    {
        $weeklyLimit = Mage::getStoreConfig('rewards/rewardssocial2/weekly_limit');
        if (!$weeklyLimit) {
            return true;
        }
        
        $datetime = Mage::helper('rewards/datetime')
            ->getZendDate(null, true)
            ->settime('0:00:00');
        $datetime = $datetime
            ->sub($datetime->get(Zend_Date::WEEKDAY_DIGIT), Zend_date::DAY)
            ->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND);

        $requestsInThePastWeek = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', array('gt' => $datetime))
            ->getSize();

        return ($requestsInThePastWeek < $weeklyLimit);
    }
    
    /**
     * Validate monthly social request limits
     * 
     * @param int $customerId
     * @return bool
     */
    public function isWithinMonthlyLimit($customerId)
    {
        $monthlyLimit = Mage::getStoreConfig('rewards/rewardssocial2/monthly_limit');
        if (!$monthlyLimit) {
            return true;
        }
        
        $datetime = Mage::helper('rewards/datetime')
            ->getZendDate(null, true)
            ->settime('0:00:00');
        $datetime = $datetime
            ->sub($datetime->get(Zend_Date::DAY_SHORT) - 1, Zend_date::DAY)
            ->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND);

        $requestsInThePastMonth = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', array('gt' => $datetime))
            ->getSize();

        return ($requestsInThePastMonth < $monthlyLimit);
    }
    
    /**
     * Validate yearly social request limits
     * 
     * @param int $customerId
     * @return bool
     */
    public function isWithinYearlyLimit($customerId)
    {
        // Yearly Limit Check
        $yearlyLimit = Mage::getStoreConfig('rewards/rewardssocial2/yearly_limit');
        if (!$yearlyLimit) {
            return true;
        }
        
        $datetime = Mage::helper('rewards/datetime')
            ->getZendDate(null, true)
            ->settime('0:00:00');
        $datetime = $datetime
            ->sub($datetime->get(Zend_Date::DAY_OF_YEAR), Zend_date::DAY)
            ->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND);

        $requestsInThePastYear = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', array('gt' => $datetime))
            ->getSize();

        return ($requestsInThePastYear < $yearlyLimit);
    }
    
    /**
     * Validate lifetime social request limits
     * 
     * @param int $customerId
     * @return bool
     */
    public function isWithinLifetimeLimit($customerId)
    {
        $lifetimeLimit = Mage::getStoreConfig('rewards/rewardssocial2/lifetime_limit');
        if (!$lifetimeLimit) {
            return true;
        }
        
        $lifetimeRequests = Mage::getModel('rewardssocial2/action')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->getSize();
        
        return ($lifetimeRequests < $lifetimeLimit);
    }
}
