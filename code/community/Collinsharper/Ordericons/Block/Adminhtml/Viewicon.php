<?php

class Collinsharper_Ordericons_Block_Adminhtml_Viewicon extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
       $this->setTemplate('chordericons/viewblock.phtml');
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }
    
    public function getImages() {
        return Mage::helper('chordericons')->getOrderIcons($this->getOrder());
    }

}