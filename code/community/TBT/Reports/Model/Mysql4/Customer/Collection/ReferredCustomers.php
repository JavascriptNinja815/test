<?php

class TBT_Reports_Model_Mysql4_Customer_Collection_ReferredCustomers extends TBT_Reports_Model_Mysql4_Customer_Collection_Abstract
{
    /**
     * Do some checks on the collection and it's flags,
     * then create query based on referral table
     *
     * @throws Exception if there's a problem with the flags set
     * @return Varien_Db_Select
     */
    public function getCustomerIdsSelect()
    {
        $this->checkCollection();
        return $this->_getCustomerIdsSelect();
    }


    /**
     * Apply the current filter to our collection
     */
    public function applyToCollection()
    {
        $this->checkCollection();
        $this->getCollection()->addFieldToFilter('entity_id', array(
            'in' => $this->_getCustomerIdsSelect()
        ));
    }

    /**
     * Internal function to get customer ids select statement
     * @param bool $doJoin
     * @return arien_Db_Select
     */
    protected function _getCustomerIdsSelect()
    {
        $select = $this->_getSelect();
        return $this->_getHelper('tbtreports/collection')->extractColumn($select, 'referral_child_id');
    }

    /**
     * @param bool $doJoin should join with customer table for startDate and endDate or not?
     * @return Varien_Db_Select
     */
    protected function _getSelect()
    {
        $endDate = $this->getCollection()->getEndDate();
        $startDate = $this->getCollection()->getStartDate();

        $referrals = Mage::getModel('rewardsref/referral')
            ->getCollection()
            ->excludeCustomerData();

        if (($startDate || $endDate)) {
            $this->_joinCustomerTable($referrals);
            $this->_applyPeriodLimitsToJoin($referrals);
        }

        return $referrals->getSelect();
    }

    /**
     * Perform join operation on collection object
     * @param Varien_Data_Collection_Db $collection
     * @return Varien_Data_Collection_Db
     */
    protected function _joinCustomerTable(&$collection)
    {
        $collection->getSelect()->reset(Varien_Db_Select::COLUMNS);
        $collection->getSelect()->joinInner(
            array('customer_table' => $collection->getTable('customer/entity')),
            "main_table.referral_child_id = customer_table.entity_id",
            "customer_table.*"
        );

        return $collection;
    }

    /**
     * Will apply start and end dates to the join on the collection object
     * @param Varien_Data_Collection_Db $collection
     * @return Varien_Data_Collection_Db
     */
    protected function _applyPeriodLimitsToJoin(&$collection)
    {
        $endDate = $this->getCollection()->getEndDate();
        $startDate = $this->getCollection()->getStartDate();

        if ($startDate)
            $collection->addFieldToFilter('customer_table.created_at', array('gteq' => $startDate));
        if ($endDate)
            $collection->addFieldToFilter('customer_table.created_at', array('lteq' => $endDate));

        // No need for parent collection to apply period limits any more
        $parentCollection = $this->getCollection();
        $parentCollection->shouldApplyPeriodLimits(false);

        return $collection;
    }
}