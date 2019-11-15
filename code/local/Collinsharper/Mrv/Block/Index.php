<?php   
class Collinsharper_Mrv_Block_Index extends Mage_Core_Block_Template{   

    const MY_RETRO_VIEWER_CAMPAIGN_SUFFIX = "?campaign=mrv";

    function setRetailCustomer(){
        Mage::getSingleton('core/session')->setRetailCustomer(true);
    }

    function getLoginUrl() {
        $loginLink = Mage::helper('customer')->getLoginUrl() . $this::MY_RETRO_VIEWER_CAMPAIGN_SUFFIX;

    }

    function getRegisterUrl() {
        $registerLink = Mage::helper('customer')->getRegisterUrl() . $this::MY_RETRO_VIEWER_CAMPAIGN_SUFFIX;
    }


}