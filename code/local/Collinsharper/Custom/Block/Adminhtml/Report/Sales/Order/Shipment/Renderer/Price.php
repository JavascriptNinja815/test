<?php
class Collinsharper_Custom_Block_Adminhtml_Report_Sales_Order_Shipment_Renderer_Price extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency
{
     public function render(Varien_Object $row)
     {
         $data = $row->getData($this->getColumn()->getIndex());
         $data = Mage::app()->getLocale()->currency('USD')->toCurrency(floatval(preg_replace('/,/','',$data)));
         return $data;
     }
}