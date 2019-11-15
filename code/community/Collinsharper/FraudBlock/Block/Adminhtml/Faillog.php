<?php
class Collinsharper_FraudBlock_Block_Adminhtml_Faillog extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_faillog';
        $this->_blockGroup = 'fraudblock';
        $this->_headerText = Mage::helper('fraudblock')->__('Checkout Log');
//        $this->_addButtonLabel = Mage::helper('fraudblock')->__('Add Ban');

        parent::__construct();
        $this->_removeButton('add');

    }
}
