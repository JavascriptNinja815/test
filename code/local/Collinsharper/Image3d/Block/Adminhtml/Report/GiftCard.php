<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_GiftCard extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    
    public function __construct()
    {
        $this->_controller = 'adminhtml_report_giftCard';
        $this->_headerText = Mage::helper('sales')->__('Gift Cards');
        $this->_blockGroup = 'chimage3d';
        parent::__construct();
        $this->_removeButton('add');
    }
        
}
