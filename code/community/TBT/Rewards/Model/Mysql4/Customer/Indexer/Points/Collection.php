<?php

class TBT_Rewards_Model_Mysql4_Customer_Indexer_Points_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        $this->_init ( 'rewards/customer_indexer_points' );
    }

    /**
     * Will join points balance data onto customer collection that was passed in.
     * @param Mage_Customer_Model_Resource_Customer_Collection|TBT_Rewards_Model_Mysql4_Customer_Collection $customerCollection
     * @return Mage_Customer_Model_Resource_Customer_Collection|TBT_Rewards_Model_Mysql4_Customer_Collection original collection
     */
    public function joinPointsBalance(&$customerCollection)
    {
        $customerCollection->getSelect()
            ->joinLeft(
                array('indexer' => $this->_mainTable),
                "e.entity_id = indexer.customer_id"
            );

        return $customerCollection;
    }
}
