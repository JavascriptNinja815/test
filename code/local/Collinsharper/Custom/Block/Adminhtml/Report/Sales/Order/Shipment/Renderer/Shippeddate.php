<?php
class Collinsharper_Custom_Block_Adminhtml_Report_Sales_Order_Shipment_Renderer_Shippeddate extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency
{
     public function render(Varien_Object $row)
     {
         $data = $row->getData($this->getColumn()->getIndex());
         if($data)
         {
            return Mage::helper('core')->formatDate($data, 'medium', true);
         }
         else{
            $incrementId = $row->getIncrementId();
            $order = Mage::getModel('sales/order')->load($incrementId, 'increment_id');
            $orderId = $order->getId();
            $shiped = Mage::getModel('sales/order_shipment')->load($orderId,'order_id');
            return  Mage::helper('core')->formatDate($shiped->getCreatedAt(), 'medium', true);
         }
        
     }
}