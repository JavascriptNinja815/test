<?php

class TBT_Reports_Model_Customer extends TBT_Rewards_Model_Customer
{
    /**
     * (cast) the supplied customer object into a reports customer (this instance)
     * @param Mage_Customer_Model_Customer $customer
     * @return $this
     */
    public function setCustomerObject($customer)
    {
        $this->setData($customer->getData());
        return $this;
    }

    /**
     * Alias function for wasLoyal where $date is current date & time.
     * @see TBT_Reports_Model_Customer::wasLoyal()
     * @return bool
     */
    public function isLoyal()
    {
        return $this->wasLoyal(null, 0);
    }


    /**
     * Will check if customer was considered loyal at the given date.
     * The criteria for a customer to be considered loyal consist of:
     *  - if customer was referred and signed up by & including provided date.
     *  - if customer has a non-expiry redemption before or on the provided date.
     *  - if customer has reached a milestone (excluding inactivity) for a tired program
     *      by or on the provided date.
     * - if customer has any earnings as a result of placing an order including & within past year from provided date as well as
     * having at least one other earning aside from birthday points, inactivity milestone or earning from orders
     * @see TBT_Reports_Model_Mysql4_Customer_Collection_LoyaltyCustomers::__getSelect()
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @param null|string|integer|Zend_Date|array $date, date to check against (UTC time). null is now.
     * @return bool
     */
    public function wasLoyal($date = null, $offsetInSeconds = 0)
    {
        $positiveOrders = $this->countOrdersWithEarningsWithinOneYear($date, $offsetInSeconds);
        
        if ($this->wasReferred($date, $offsetInSeconds)) return true;
        if ($this->hasRedemptions($date, $offsetInSeconds)) return true;
        if ($this->reachedTiredMilestones($date, $offsetInSeconds)) return true;
        if ($positiveOrders >= 2 || 
                ($positiveOrders >=1 && $this->hadNonOrderEarningsWithinOneYear($date, $offsetInSeconds))) {
            return true;
        }

        return false;
    }

    /**
     * Will return the referral record instance in which this customer was referred by
     * @return TBT_RewardsReferral_Model_Referral
     */
    public function getReferredByRecord()
    {
        $this->_checkCustomer();
        $collection = Mage::getModel('rewardsref/referral')
            ->getCollection()
            ->excludeCustomerData()
            ->addIdOrEmailFilter($this->getId(), addslashes($this->getEmail()));

        $collection->getSelect()->limit(1);
        return $collection->getFirstItem();
    }

    /**
     * Will check to see if this customer signed up at or before the given date
     * as a result of being referred by someone else.
     *
     * @param null|string|integer|Zend_Date|array $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return bool
     */
    public function wasReferred($date = null, $offsetInSeconds = 0)
    {
        $this->_checkCustomer();
        
        $date = $this->_getDateTimeHelper()->addOffsetToDate($date, $offsetInSeconds);
        $date = $this->_getDateTimeHelper()->getZendDate($date);
        
        $signupDateString = $this->_getDateTimeHelper()->reformatDateString($this->getCreatedAt());
        $signup = $this->_getDateTimeHelper()->getZendDate($signupDateString);
        
        /*
         * 'created_at' dates on the Customer object in Magento are buggy as they are sometimes
         * saved in the store locale's timezone instead of UTC.
         * There's no (easy) way for us to know how accurate the signup dates are, so we'll give
         * ourselves a 24-hour buffer at the cost of some accuracy
         * See https://github.com/sweettooth-legacy/sweettoothrewards-magento/commit/9bd957a595652c20cff08a1b6e8c44f3fe9b63bd#commitcomment-13439570
         */
        $signup->sub(1, Zend_Date::DAY);

        
        if ($signup->isEarlier($date) || $signup->equals($date)) {
            $referredByRecord = $this->getReferredByRecord();
            if ($referredByRecord->getId()) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Will return all non-expiry redemptions for this customer happening at or before the given date
     * @param null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return TBT_Reports_Model_Mysql4_Transfer_Collection
     */
    public function getRedemptions($date = null, $offsetInSeconds = 0)
    {
        $this->_checkCustomer();
        $date = $this->_getDateTimeHelper()->addOffsetToDate($date, $offsetInSeconds);
        $date = $this->_getDateTimeHelper()->getZendDate($date);
        $redemptions = $this->_getTransferCollection()
            ->filterTransferMode()
            ->addFieldToFilter('customer_id', $this->getId())
            ->onlyNegativeTransfers()
            ->excludeExpiryTransfers()
            ->limitPeriod(null, $date->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND));

        return $redemptions;
    }

    /**
     * Will check to see if this customer has had a non-expiry redemption at or before the given date
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @param null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @return bool
     */
    public function hasRedemptions($date = null, $offsetInSeconds = 0)
    {
        $redemptions = $this->getRedemptions($date, $offsetInSeconds);
        if ($redemptions->getSize() > 0) {
            return true;
        }

        return false;
    }


    /**
     * Will return a collection of tired milestone logs which the customer reached at or before the date specified
     * @param  null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return TBT_Reports_Model_Mysql4_Milestone_Log_Collection
     */
    public function getLogOfTiredMilestonesReached($date = null, $offsetInSeconds = 0)
    {
        $this->_checkCustomer();
        $date = $this->_getDateTimeHelper()->addOffsetToDate($date, $offsetInSeconds);
        $date = $this->_getDateTimeHelper()->getZendDate($date);
        $milestoneLogs = $this->_getMilestoneLogCollection()
            ->addFieldToFilter('customer_id', $this->getId())
            ->onlyCustomerGroupActions()
            ->excludeConditionTypes("inactivity")
            ->limitPeriod(null, $date->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND));

        return $milestoneLogs;
    }

    /**
     * Will check to see if this customer reached a tired milestone goal (aside from inactivity)
     *  at or before the given date
     * @param  null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return bool
     */
    public function reachedTiredMilestones($date = null, $offsetInSeconds = 0)
    {
        $milestoneLogs = $this->getLogOfTiredMilestonesReached($date, $offsetInSeconds);
        if ($milestoneLogs->getSize() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Will return a collection of orders placed before or at specified date, which had point earnings on them
     * @param  null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @param bool $returnOrderCollection (default: true), if false, will return a collection of order ids only
     * @return Mage_Sales_Model_Mysql4_Order_Collection |TBT_Reports_Model_Mysql4_Transfer_Collection
     * @throws Exception
     */
    public function getOrdersWithEarningsWithinOneYear($date = null, $offsetInSeconds = 0, $returnOrderCollection = true)
    {
        $this->_checkCustomer();
        $date = $this->_getDateTimeHelper()->addOffsetToDate($date, $offsetInSeconds);
        $endDate = $this->_getDateTimeHelper()->getZendDate($date);
        $startDate = clone $endDate;
        $startDate->sub(1, Zend_Date::YEAR);

        $orderTransfers = $this->_getTransferCollection()
            ->filterTransferMode()
            ->addFieldToFilter('customer_id', $this->getId())
            ->onlyPositiveTransfers()
            ->onlyForReasons(Mage::helper('rewards/transfer_reason')->getReasonId('order'))
            ->limitPeriod(
                $startDate->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND),
                $endDate->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND)
            )->forceJoins();
        $orderTransfersSelect = $orderTransfers->prepareCollection()->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('reference_id')
            ->group('reference_id');

        if (!$returnOrderCollection) return $orderTransfers;
        return Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter("entity_id", array('in' => $orderTransfersSelect));

    }
    
    /**
     * Get all positive orders placed anytime upto 1 years preceding and
     * including the date suplied
     * 
     * @param  null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return int
     */
    public function countOrdersWithEarningsWithinOneYear($date = null, $offsetInSeconds = 0)
    {
        return $this->getOrdersWithEarningsWithinOneYear($date, $offsetInSeconds, false)->getSize();
    }

    /**
     * Will return a collection of transfers earned before or at specified date which are NOT as a result of:
     * birthday points, inactivity points, or having placed an order
     *
     * @param  null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return TBT_Reports_Model_Mysql4_Transfer_Collection
     * @throws Exception
     */
    public function getNonOrderEarningsWithinOneYear($date = null, $offsetInSeconds = 0)
    {
        $this->_checkCustomer();
        $date = $this->_getDateTimeHelper()->addOffsetToDate($date, $offsetInSeconds);
        $endDate = $this->_getDateTimeHelper()->getZendDate($date);
        $startDate = clone $endDate;
        $startDate->sub(1, Zend_Date::YEAR);

        $reasonHelper = Mage::helper('rewards/transfer_reason');
        $orderTransfers = $this->_getTransferCollection()
            ->filterTransferMode()
            ->addFieldToFilter('customer_id', $this->getId())
            ->onlyPositiveTransfers()
            ->excludeReasons(
                $reasonHelper->getReasonId('birthday'),
                $reasonHelper->getReasonId('milestone_inactivity'),
                $reasonHelper->getReasonId('order')
            )
            ->limitPeriod(
                $startDate->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND),
                $endDate->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND)
            );

        return $orderTransfers;
    }

    /**
     * Will check if customer has at least 1 earning transaction not resulting from one of:
     * birthday points, inactivity points, or having placed an order,
     * anytime upto 1 year preceding and including the date supplied.
     *
     * @param  null|string|integer|Zend_Date $date, date to check against (UTC time). null is now.
     * @param int $offsetInSeconds (optional), specify number of negative or positive seconds to add to $date
     * @return bool
     */
    public function hadNonOrderEarningsWithinOneYear($date = null, $offsetInSeconds = 0)
    {
        $orderTransfers = $this->getNonOrderEarningsWithinOneYear($date, $offsetInSeconds);
        if ($orderTransfers->getSize() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Error checking in case there's no customer
     * @throws Exception
     */
    protected function _checkCustomer()
    {
        if (!$this->getId()) {
            throw new Exception('You can make this query only on an existing customer');
        }
    }

    /**
     * @param string $helper (default: tbtreport)
     * @return TBT_Reports_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function _getHelper($helper = 'tbtreports')
    {
        return Mage::helper($helper);
    }

    /**
     * @return TBT_Rewards_Helper_Datetime;
     */
    protected function _getDateTimeHelper()
    {
        return $this->_getHelper('rewards/datetime');
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Transfer_Collection
     */
    protected function _getTransferCollection()
    {
        return Mage::getResourceModel('tbtreports/transfer_collection');
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Milestone_Log_Collection
     */
    protected function _getMilestoneLogCollection()
    {
        return Mage::getResourceModel('tbtreports/milestone_log_collection');
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Order_Collection
     */
    protected function _getOrderCollection()
    {
        return Mage::getResourceModel('tbtreports/order_collection');
    }
}