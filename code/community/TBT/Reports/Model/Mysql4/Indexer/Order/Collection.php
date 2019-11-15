<?php

class TBT_Reports_Model_Mysql4_Indexer_Order_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('tbtreports/indexer_order');
    }

    /**
     * @param bool $returnSelectObject
     * @return array|Varien_Db_Select
     */
    public function getOrderIds($returnSelectObject = true)
    {
        $clone = clone $this;
        if (!$returnSelectObject) {
            return $clone->getAllIds();
        }

        return Mage::helper('tbtreports/collection')->extractColumn($clone, 'order_id');
    }

    /**
     * @return int|null smallest id in the table
     */
    public function getEarliestId()
    {
        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::COLUMNS)
            ->columns(new Zend_Db_Expr("MIN(`order_id`)"));

        return $this->getConnection()->fetchOne($select);
    }

}