<?php

class TBT_Reports_Model_Mysql4_Milestone_Log_Collection extends TBT_Milestone_Model_Resource_Rule_Log_Collection
{
    protected $_flags = array();
    protected $_isPrepared = false;

    /**
     * Will limit this collection to logs where the action was "customergroup"
     * @return $this
     */
    public function onlyCustomerGroupActions()
    {
        $this->_flags['only_customergroup_actions'] = true;
        $this->addFieldToFilter('action_type', TBT_Rewards_Model_Special_Action::ACTION_TYPE_CUSTOMER_GROUP);

        return $this;
    }

    /**
     * Excludes specified condition types from this collection
     * @return $this
     */
    public function excludeConditionTypes(/* $args */)
    {
        $conditionTypes = func_get_args();
        $this->_flags['exclude_condition_types'] = $this->isFlagSet('exclude_condition_types') ?
            array_merge($this->_flags['exclude_condition_types'], $conditionTypes) : $conditionTypes;

        /* Has to be implemented in _prepareCollection() */

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
        $clone = clone $this;
        $clone->prepareCollection();
        return $this->_getHelper('tbtreports/collection')->extractColumn($clone, 'customer_id', $returnSelectObject);
    }

    /**
     * Will add start and end dates to the query to filter milestone execution time
     *
     * @param string $startDate (optional), UTC timezone
     * @param string $endDate (optional), UTC timezone
     * @return $this
     */
    public function limitPeriod($startDate = null, $endDate = null)
    {
        if ($startDate) {
            $this->_flags['start_date'] = $startDate;
            $this->addFieldToFilter('executed_date', array('gteq' => $startDate));
        }

        if ($endDate) {
            $this->_flags['end_date'] = $endDate;
            $this->addFieldToFilter('executed_date', array('lteq' => $endDate));
        }

        return $this;
    }

    /**
     * Will perform any last minute things that need to be done to the collection before it's loaded
     * @return $this
     */
    public function prepareCollection()
    {
        if (!$this->_isPrepared) {
            $this->_isPrepared = true;

            // Prepare exclude_condition_types
            if ($this->isFlagSet('exclude_condition_types')) {
                $this->addFieldToFilter('condition_type', array('nin' => $this->_flags['exclude_condition_types']));
            }
        }

        return $this;
    }

    /**
     * Need to take into account call to prepareCollection()
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        if (!$this->_isPrepared) {
            $cloneCollection = clone $this;
            $cloneCollection->prepareCollection();
            return $cloneCollection->getSelectCountSql();
        }

        return parent::getSelectCountSql();
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
     * Overwrite to explicitly prepare the collection before loading
     * @return $this|Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    protected function _beforeLoad()
    {
        $this->prepareCollection();
        return parent::_beforeLoad();
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