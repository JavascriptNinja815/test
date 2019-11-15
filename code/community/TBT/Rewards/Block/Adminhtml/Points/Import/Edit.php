<?php

class TBT_Rewards_Block_Adminhtml_Points_Import_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'rewards';
        $this->_controller = 'adminhtml_points_import';

        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Schedule Import'));		 
    }

    public function getHeaderText()
    {
        return Mage::helper('adminhtml')->__('New Points Import');
    }
}
