<?php

class Bronto_Product_Model_Collect_Custom extends Bronto_Product_Model_Collect_Abstract
{
    /**
     * @see parent
     */
    public function collect()
    {
        $productIds = $this->_recommendation->getCustomProductIds($this->_source);
        if (empty($productIds)) {
            return array();
        }
        $custom = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('entity_id', array('id' => $productIds));
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($custom);
        return $this->_fillProducts($custom);
    }
}
