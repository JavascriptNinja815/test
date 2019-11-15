<?php
class Collinsharper_FraudBlock_Block_Adminhtml_Fraudblock extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_fraudblock';
        $this->_blockGroup = 'fraudblock';
        $this->_headerText = Mage::helper('fraudblock')->__('Manage Ban');
        $this->_addButtonLabel = Mage::helper('fraudblock')->__('Add Ban');
        parent::__construct();
    }
}
