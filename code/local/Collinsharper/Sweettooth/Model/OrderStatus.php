<?php

class Collinsharper_Sweettooth_Model_OrderStatus extends Mage_Core_Model_Abstract
{
    
    public function toOptionArray() {
        $data = array();
        foreach (Mage::getModel('sales/order_status')->getResourceCollection()->getData() as $status) {
            $data[] = array('value' => $status['status'], 'label' => $status['label']);
        }
        return $data;
    }
    
}