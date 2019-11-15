<?php

class Collinsharper_Chcustomeruploads_Block_Tab 
extends Mage_Adminhtml_Block_Template 
implements Mage_Adminhtml_Block_Widget_Tab_Interface {
    
    public function __construct()
    {
        //parent::__construct();
//        $this->setTemplate('uploader/order/view/tab/uploader.phtml');
    }

    public function getTabLabel() {
        return 'xxx';
    }

    public function getTabTitle() {
        return $this->__('Tab title');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }
    
}
