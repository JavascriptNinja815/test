<?php
class TBT_Reports_Model_Mysql4_Transfer_Collection extends TBT_Rewards_Model_Mysql4_Transfer_Collection
{
    protected $_flags = array();
    protected $_isPrepared = false;

    /**
     * Overwrite so we return instances of tbtreports/transfer
     * but we use original resource model
     *
     * @see TBT_Rewards_Model_Mysql4_Transfer_Collection::_construct();
     */
    public function _construct()
    {
        $this->_init('rewards/transfer');
    }

    /**
     * Will return results which exclude expiry transfers
     *
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function excludeExpiryTransfers()
    {
        $this->flags['exclude_expiry_transfers'] = true;
        $this->_contradicts('only_expiry_transfers', 'only_positive_transfers');

        $this->addFieldToFilter('reason_id', array('neq' => Mage::helper('rewards/transfer_reason')->getReasonId('expire')));

        return $this;
    }

    /**
     * Will limit the collection to expiry transfers only
     *
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function onlyExpiryTransfers()
    {
        $this->flags['only_expiry_transfers'] = true;
        $this->_contradicts('exclude_expiry_transfers', 'only_for_reasons');

        $this->onlyNegativeTransfers();
        $this->addFieldToFilter('reason_id', array('neq' => Mage::helper('rewards/transfer_reason')->getReasonId('expire')));

        return $this;
    }

    /**
     * Will limit the collection to positive transfers only (earnings)
     *
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function onlyPositiveTransfers()
    {
        $this->flags['only_positive_transfers'] = true;
        $this->_contradicts('only_expiry_transfers');

        $this->addFieldToFilter('quantity', array('gt' => 0));

        return $this;
    }

    /**
     * Will limit the collection to negative transfers only (spendings)
     * @return $this
     */
    public function onlyNegativeTransfers()
    {
        $this->flags['only_negative_transfers'] = true;
        $this->addFieldToFilter('quantity', array('lt' => 0));

        return $this;
    }

    /**
     * Will limit the collection to live transfers only
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function onlyLiveTransfers()
    {
        $this->flags['only_live_transfers'] = true;
        $this->_contradicts('only_dev_transfers');

        $this->addFieldToFilter('is_dev_mode', array('eq' => 0));

        return $this;
    }

    /**
     * Will limit the collection to dev transfers only
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function onlyDevTransfers()
    {
        $this->flags['only_dev_transfers'] = true;
        $this->_contradicts('only_live_transfers');

        $this->addFieldToFilter('is_dev_mode', array('eq' => 1));

        return $this;
    }

    /**
     * Depending on system configuration,
     * will set the collection to dev mode or live mode or both.
     *
     * @return $this
     */
    public function filterTransferMode()
    {
        $queryInDevMode = $this->_getHelper()->shouldReportOnDevMode();
        $queryInLiveMode = $this->_getHelper()->shouldReportOnLiveMode();

        if ($queryInDevMode && $queryInLiveMode) {
            // no need to filter anything

        } else if ($queryInDevMode) {
            $this->onlyDevTransfers();

        } else if ($queryInLiveMode) {
            $this->onlyLiveTransfers();

        } else {
            throw new Exception("Can't exclude both \"live\" and \"dev\" transfers");
        }

        return $this;
    }

    /**
     * Will limit the collection to provided reasons only
     *
     * @param int|string|array $reasons, any number of reasons
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function onlyForReasons(/* $args */)
    {
        $reasons = func_get_args();
        $this->_contradicts('only_expiry_transfers', 'exclude_reasons');
        $this->_flags['only_for_reasons'] = $this->isFlagSet('only_for_reasons') ?
            array_merge($this->_flags['only_for_reasons'], $reasons) : $reasons;

        /* Has to be implemented in prepareCollection() */

        return $this;
    }

    /**
     * Will exclude specified reasons from collection
     *
     * @param int|string|array $reasons, any number of reasons
     * @return $this
     * @throws Exception if a contradictory query is being built
     */
    public function excludeReasons(/* $args */)
    {
        $reasons = func_get_args();
        $this->_contradicts('only_expiry_transfers', 'only_for_reasons');
        $this->_flags['exclude_reasons'] = $this->isFlagSet('exclude_reasons') ?
            array_merge($this->_flags['exclude_reasons'], $reasons) : $reasons;

        /* Has to be implemented in prepareCollection() */

        return $this;
    }

    /**
     * Will add start and end dates to the query to filter transaction's by created time
     *
     * @param string $startDate (optional), UTC timezone
     * @param string $endDate (optional), UTC timezone
     * @return $this
     */
    public function limitPeriod($startDate = null, $endDate = null)
    {
        if ($startDate) {
            $this->flags['has_start_date'] = true;
            $this->addFieldToFilter('created_at', array('gteq' => $startDate));
        }

        if ($endDate) {
            $this->flags['has_end_date'] = true;
            $this->addFieldToFilter('created_at', array('lteq' => $endDate));
        }

        return $this;
    }

    /**
     * Will force the collection to use joins as apposed to sub-queries whenever possible.
     * @return $this
     */
    public function forceJoins()
    {
        $this->_flags['force_joins'] = true;

        /* Has to be implemented in prepareCollection() */

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
     * Will perform any last minute things that need to be done to the collection before it's loaded
     * @param $collection (optional) will do all operations on the collection object specified instead of $this
     * @return TBT_Reports_Model_Mysql4_Transfer_Collection same collection that was passed in ($this?)
     */
    public function prepareCollection()
    {
        if ($this->_isPrepared) return $this;

        // Prepare only_for_reasons
        if ($this->isFlagSet('only_for_reasons')) {
            $this->addFieldToFilter('reason_id', array('in' => $this->_flags['only_for_reasons']));
        }

        // Prepare exclude_reasons
        if ($this->isFlagSet('exclude_reasons')) {
            $this->addFieldToFilter('reason_id', array('nin' => $this->_flags['exclude_reasons']));
        }

        $this->_isPrepared = true;
        return $this;
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
     * Need to take into account call to prepareCollection()
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $cloneCollection = clone $this;
        $cloneCollection->prepareCollection();

        return $this->getConnection()
            ->select()
            ->from($cloneCollection->getSelect())
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('COUNT(*)');
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