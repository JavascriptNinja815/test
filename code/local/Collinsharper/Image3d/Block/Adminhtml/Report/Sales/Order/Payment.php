<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Payment extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    
    public function __construct()
    {
        $this->_controller = 'adminhtml_report_sales_order_payment';
        $this->_headerText = Mage::helper('sales')->__('Deposits Received');
        $this->_blockGroup = 'chimage3d';
        parent::__construct();
        $this->_removeButton('add');
        $this->_addButton('refresh', array(
            'label'     => Mage::helper('chimage3d')->__('Refresh Stats'),
            'onclick'   => "setLocation('{$this->getUrl('*/*/refresh')}')"
        ));
    }
        
}
