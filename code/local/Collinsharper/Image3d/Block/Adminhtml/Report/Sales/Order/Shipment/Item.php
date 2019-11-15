<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Shipment_Item extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    
    public function __construct()
    {
        $this->_controller = 'adminhtml_report_sales_order_shipment_item';
        $this->_headerText = Mage::helper('sales')->__('Shipped Items');
        $this->_blockGroup = 'chimage3d';
        parent::__construct();
        $this->_removeButton('add');
    }
        
}
