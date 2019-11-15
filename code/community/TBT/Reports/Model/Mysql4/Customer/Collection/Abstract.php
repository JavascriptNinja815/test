<?php

/**
 * Class TBT_Reports_Model_Mysql4_Customer_Collection_Abstract
 * @method TBT_Reports_Model_Mysql4_Customer_Collection getCollection()
 * @method $this setCollection(TBT_Reports_Model_Mysql4_Customer_Collection $collection)
 */
abstract class TBT_Reports_Model_Mysql4_Customer_Collection_Abstract extends Varien_Object
{
    /**
     * Subclass will return select statement for customer Ids
     * @return mixed
     */
    public abstract function getCustomerIdsSelect();

    /**
     * Will apply filter of the subclass to the parent collection
     * @return mixed
     */
    public abstract function applyToCollection();

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
     * @return TBT_Rewards_Helper_Datetime
     */
    protected function _getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
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
     * Check if there is a parent collection available
     * @throws Exception
     */
    protected function checkCollection()
    {
        if (!$this->getCollection()) {
            throw new Exception("Collection has not been set.");
        }
    }

    /**
     * Will return value of flag on collection
     * @param string $flag
     * @return mixed|null
     */
    protected function getCollectionFlag($flag)
    {
        if (!$this->getCollection()->isFlagSet($flag)) {
            return null;
        }

        $flags = $this->getCollection()->getFlags();
        return $flags[$flag];
    }
}