<?php
class Collinsharper_Inquiry_Block_Adminhtml_Inquiry extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_inquiry';
        $this->_blockGroup = 'chinquiry';
        $this->_headerText = Mage::helper('chinquiry')->__('Inquiry');
        parent::__construct();
        $this->_removeButton('add');

    }
}
