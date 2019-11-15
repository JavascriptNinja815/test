<?php
class Collinsharper_Ordericons_Block_Adminhtml_Chicons extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_chicons';
        $this->_blockGroup = 'chordericons';
        $this->_headerText = Mage::helper('chordericons')->__('Manage Items');
        $this->_addButtonLabel = Mage::helper('chordericons')->__('Add Item');
        parent::__construct();
    }
}
