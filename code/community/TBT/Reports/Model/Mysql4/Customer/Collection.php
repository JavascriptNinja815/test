<?php


class TBT_Reports_Model_Mysql4_Customer_Collection extends TBT_Rewards_Model_Mysql4_Customer_Collection
{
    protected $_flags = array();
    protected $_applyPeriodLimits = true;
    protected $_isPrepared = false;

    /**
     * Overwrite to return instances of this module instead
     */
    public function _construct()
    {
        $this->_init('tbtreports/customer', 'rewards/customer');
    }

    /**
     * Filter for loyalty customers only
     * @return $this
     * @throws Exception
     * @see TBT_Reports_Model_Mysql4_Customer_Collection_LoyaltyCustomers
     */
    public function onlyLoyaltyCustomers()
    {
        $this->_checkIfAlreadyLoaded();
        $this->_checkExclusivity('only_loyalty_customers');
        $this->_assignResourceToFlag('loyaltyCustomers', 'only_loyalty_customers');


        return $this;
    }

    /**
     * If this flag is set along with `onlyLoyaltyCustomers()`, the collection
     * will contain customers who were *ever* "loyal" during the specified period.
     * This means, if a customer is considered "loyal" on the first day of the period but
     * is not considered "loyal" midway through the period,
     * they will still be included in the collection.
     */
    public function everLoyalDuringPeriod()
    {
        $this->_checkIfAlreadyLoaded();
        $this->_contradicts('always_loyal_during_period');
        $this->_flags['ever_loyal_during_period'] = true;

        return $this;
    }

    /**
     * If this flag is set along with `onlyLoyaltyCustomers()`, the collection
     * will contain only customers who were *always* "loyal" during the specified period.
     * This means, if a customer is considered "loyal" on the first day of the period but
     * is not considered "loyal" midway through the period,
     * they will not be included in the collection.
     */
    public function alwaysLoyalDuringPeriod()
    {
        $this->_checkIfAlreadyLoaded();
        $this->_contradicts('ever_loyal_during_period');
        $this->_flags['always_loyal_during_period'] = true;

        return $this;
    }

    /**
     * Set this query to depend on a parent query table based on the order table
     * @return $this
     */
    public function dependsOnOrderTable()
    {
        $this->_flags['dependent_on_orders'] = true;
        return $this;
    }

    /**
     * Returns a collection of customers who have been referred between startDate and endDate
     */
    public function onlyReferredCustomers()
    {
        $this->_checkIfAlreadyLoaded();
        $this->_checkExclusivity('only_referred_customers');
        $this->_assignResourceToFlag('referredCustomers', 'only_referred_customers');

        return $this;
    }

    /**
     * Returns a collection of customers who are not considered "loyalty customers"
     * @see TBT_Reports_Helper_Customer::getLoyaltyCustomers()
     *
     * @todo requires implementation
     * @param string $startDate
     * @param string $endDate
     * @return TBT_Rewards_Model_Mysql4_Customer_Collection
     */
    public function onlyOtherCustomers()
    {
        throw new Exception("Method not implemented yet");
    }

    /**
     * Returns a collection of all Magento customers which have placed & paid for at least 1 order
     * between startDate & endDate
     *
     * @todo requires implementation
     * @param string $startDate
     * @param string $endDate
     * @return TBT_Rewards_Model_Mysql4_Customer_Collection
     */
    public function onlyRealCustomers()
    {
        throw new Exception("Method not implemented yet");
    }

    /**
     * Returns a collection of all Magento customers which have placed & paid for more than 1 order
     * between startDate & endDate
     *
     * @todo requires implementation
     * @param string $startDate
     * @param string $endDate
     * @return TBT_Rewards_Model_Mysql4_Customer_Collection
     */
    public function onlyRepeatCustomers()
    {
        throw new Exception("Method not implemented yet");
    }

    /**
     * Will add start and end dates to the query
     *
     * @param string $startDate (optional), UTC timezone
     * @param string $endDate (optional), UTC timezone
     * @return $this
     */
    public function limitPeriod($startDate = null, $endDate = null)
    {
        if ($startDate) {
            $this->_flags['start_date'] = $startDate;
        }

        if ($endDate) {
            $this->_flags['end_date'] = $endDate;
        }

        return $this;
    }

    /**
     * Explicitly state if we should apply the period limits on the parent collection.
     * @param boolean $boolean
     * @return $this
     */
    public function shouldApplyPeriodLimits($boolean)
    {
        $this->_applyPeriodLimits = !!$boolean;
        return $this;
    }

    /**
     * Sets a positive or negative offset in seconds to consider for calculating endDate
     * @param int $seconds
     * @return $this
     */
    public function setEndDateOffset($seconds)
    {
        $this->_flags['end_date_offset'] = $seconds;
        return $this;
    }

    /**
     * Will return a list of customer ids for the current selection
     * Does not modify this collection or load it
     *
     * @param bool $returnSelectObject, if true, will return Varien_Db_Select object, otherwise an array
     * @return array|Varien_Db_Select
     */
    public function getCustomerIds($returnSelectObject = true)
    {
        if (!$returnSelectObject && $this->isLoaded()) {
            return $this->getLoadedIds();
        }

        if (!$returnSelectObject && $this->isFlagSet('dependent_on_orders')) {
            throw new Exception ("Can't evaluate a query that's dependant on another query.\nLoad the collection first.");
        }

        foreach ($this->_getMutuallyExclusiveFlags() as $flag){
            if ($this->isFlagSet($flag)) {
                $childResource = $this->_flags[$flag];
                $select = $childResource->getCustomerIdsSelect();
                if ($returnSelectObject) {
                    // @todo: account for non-mutually exclusive flags in the future
                    return $select;

                } else {
                    if (is_null($select)) return array();
                    return $this->_getHelper('tbtreports/collection')->loadColumn($this->getConnection(), $select, 'customer_id');
                }
            }
        }

        // If not returned yet, do this:
        if ($returnSelectObject) return $this->getAllIdsSql();
        if (!$returnSelectObject) return $this->getAllIds();
    }

    /**
     * Will perform any last minute things that need to be done to the collection before it's loaded
     * This function is intended to be shared between getCustomerIds and _beforeLoad
     *
     * @param $collection (optional) will do all operations on the collection object specified instead of $this
     * @return $this
     */
    public function prepareCollection()
    {
        if (!$this->_isPrepared) {
            $this->_isPrepared = true;

            /**
             * Apply mutually exclusive flags
             */
            foreach ($this->_getMutuallyExclusiveFlags() as $flag) {
                if ($this->isFlagSet($flag)) {
                    $childResource = $this->_flags[$flag];
                    $childResource->applyToCollection();
                    break;
                }
            }

            /**
             *  Apply period limits (start and end dates)
             */
            if ($this->_applyPeriodLimits) {
                $endDate = $this->getEndDate();
                $startDate = $this->getStartDate();
                if ($startDate) $this->addFieldToFilter('created_at', array('gteq' => $startDate));
                if ($endDate) $this->addFieldToFilter('created_at', array('lteq' => $endDate));
            }
        }

        return $this;
    }

    /**
     * Get parent collection's start date
     * @return string|null|Zend_Db_Expr
     */
    public function getStartDate()
    {
        if ($this->getFlag('dependent_on_orders')) {
            return null;
        }

        return $this->getFlag('start_date');
    }

    /**
     * Get computed end date
     * @return string|null|Zend_Db_Expr
     */
    public function getEndDate()
    {
        $endDateOffset = $this->getFlag('end_date_offset');
        if ($this->getFlag('dependent_on_orders')) {
            $endDateOffsetAbs = abs($endDateOffset);
            $createdAtString = '%s.created_at';
            if (is_int($endDateOffset) && $endDateOffset > 0) $createdAtString = "DATE_ADD({$createdAtString}, INTERVAL {$endDateOffsetAbs} SECOND)";
            if (is_int($endDateOffset) && $endDateOffset < 0) $createdAtString = "DATE_SUB({$createdAtString}, INTERVAL {$endDateOffsetAbs} SECOND)";
            return new Zend_Db_Expr(sprintf($createdAtString, TBT_Reports_Model_Mysql4_Order_Collection::TABLE_ALIAS));
        }

        $endDate = $this->getFlag('end_date');
        if (!empty($endDateOffset)) {
            $endDate = $this->_getDateTimeHelper()->addOffsetToDate($endDate, $endDateOffset);
        }

        return $endDate;
    }

    /**
     * Will return collection flags which cannot possibly be set on the same collection instance together
     * because they are mutually exclusive
     * @return array
     */
    protected function _getMutuallyExclusiveFlags()
    {
        return array(
            'only_loyalty_customers',
            'only_referred_customers',
        );
    }

    /**
     * Overwrite to explicitly prepare the collection before loading
     * @return $this|Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    protected function _beforeLoad()
    {
        $this->prepareCollection();
        return parent::_beforeLoad();
    }

    /**
     * How to count objects of this collection
     * @return Varien_Db_Select
     * @throws Exception
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = $this->getConnection()
            ->select()
            ->from($this->getCustomerIds())
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('COUNT(*)');

        return $countSelect;
    }

    /**
     * Overwrite to also clear internal flags
     * @return Varien_Data_Collection
     */
    public function clear()
    {
        $this->_flags = array();
        $this->_isPrepared = false;
        return parent::clear();
    }

    /**
     * @throws Exception if this collection has already been loaded.
     */
    protected function _checkIfAlreadyLoaded()
    {
        if ($this->isLoaded() || $this->_isPrepared) {
            throw new Exception('Collection has already been loaded.');
        }
    }

    /**
     * Will check $currentFlag to make sure it does not conflict with other
     * mutually exclusive tags.
     * @param $currentFlag
     */
    protected function _checkExclusivity($currentFlag)
    {
        $allExclusiveFlags = $this->_getMutuallyExclusiveFlags();
        $copy = array_merge($allExclusiveFlags);
        $currentIndex = array_search($currentFlag, $copy);
        if ($currentIndex !== false) {
            unset($copy[$currentIndex]);
        }

        call_user_func_array(array($this, '_contradicts'), $copy);
    }

    /**
     * Will accept any number of flag arguments and check if specified flags are set
     *
     * @param string $args. any number of string arguments
     * @throws Exception if any of the flags are set
     */
    protected function _contradicts(/* $args */)
    {
        $flagsToCheck = func_get_args();
        foreach ($flagsToCheck as $flag){
            if ($this->isFlagSet($flag)) {
                throw new Exception("Contradicting query!");
            }
        }
    }

    /**
     * Will load a child customer collection resource and assign it to the specified flag
     * Child collections implement exact queries used to filter/construct this collection
     * When a flag is set, we know to call the appropriate methods of it's child resource
     * at the right time when loading this collection or extracting customer ids
     *
     * @throws Exception if resource not found
     * @param string $child resource name (eg. loyaltyCustomers)
     * @param string $flag flag variable to set (eg. only_loyalty_customers)
     * @return TBT_Reports_Model_Mysql4_Customer_Collection_Abstract
     */
    protected function _assignResourceToFlag($child, $flag)
    {
        $resource = Mage::getResourceModel("tbtreports/customer_collection_{$child}");
        if (!$resource) {
            throw new Exception(
                "Collection resource not found!\n".
                "tbtreports/customer_collection_{$child}
            ");
        }
        $resource->setCollection($this);
        $this->_flags[$flag] = $resource;

        return $resource;
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
     * @return TBT_Rewards_Helper_Datetime
     */
    protected function _getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
    }

    /**
     * @param string $flag
     * @return bool true if $flag is set and it's value is TRUE, false otherwise
     */
    public function isFlagSet($flag)
    {
        $flags = $this->_flags;
        return !empty($flags[$flag]);
    }

    /**
     * Returns flags for this collection
     * @return array
     */
    public function getFlags()
    {
        return $this->_flags;
    }
}