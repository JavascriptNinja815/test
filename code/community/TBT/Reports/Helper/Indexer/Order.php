<?php

class TBT_Reports_Helper_Indexer_Order extends Mage_Core_Helper_Abstract
{
    const INDEXER_CODE  = 'tbtreports_indexer_order';
    const MODEL_CODE    = 'tbtreports/indexer_order';

    protected $_processModel = null;
    protected $_model = null;

    /**
     * Get Index Process model instance for this indexer
     * @return Mage_Index_Model_Process
     */
    public function getIndexProcessModel()
    {
        if (empty($this->_processModel)) {
            $collection = Mage::getModel('index/process')->getCollection();
            $collection->addFieldToFilter('indexer_code', self::INDEXER_CODE);
            $this->_processModel = $collection->getFirstItem();
        }

        return $this->_processModel;
    }

    /**
     * @return Mage_Index_Model_Indexer_Abstract
     */
    public function getIndexerModel()
    {
        if (empty($this->_model)) {
            $this->_model = Mage::getModel(self::MODEL_CODE);
        }

        return $this->_model;
    }

    /**
     * @return Mage_Core_Model_Mysql4_Collection_Abstract
     */
    public function getIndexerCollectionModel()
    {
        return Mage::getResourceModel(self::MODEL_CODE . '_collection');
    }

    /**
     * @return bool true, if indexer is in "pending" status, false otherwise!
     */
    public function isReady()
    {
        $status = $this->getIndexProcessModel()->getStatus();
        $isPending = ($status == Mage_Index_Model_Process::STATUS_PENDING);

        return $isPending;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->getIndexerModel()->getName();
    }

    /**
     * Will load earliest order in the index table and return it's createdAt() value
     * @return string
     */
    public function getEarliestRecordDate()
    {
        $earliestId = $this->getIndexerCollectionModel()->getEarliestId();
        if (is_numeric($earliestId)) {
            $earliestIndexedOrder = Mage::getModel('sales/order')->load($earliestId);
            if ($earliestIndexedOrder && $earliestIndexedOrder->getId()) {
                return $earliestIndexedOrder->getCreatedAt();
            }
        }

        return null;
    }
}