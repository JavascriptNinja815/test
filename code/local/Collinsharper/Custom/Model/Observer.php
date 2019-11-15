<?php
class Collinsharper_Custom_Model_Observer extends Varien_Object{
    public function logoutSite() {
        $store = Mage::app()->getStore();
        $name = $store->getName();
        $old_name = Mage::getSingleton('core/session')->getLogStore();
        Mage::getSingleton('core/session')->setLogStore($name);

        if($old_name && $old_name != $name)
        {
            $check = Mage::getSingleton('customer/session')->logout()->renewSession();
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            Mage::getSingleton('core/session')->setLogStore($name);
            Mage::app()->getFrontController()->getResponse()->setRedirect($currentUrl)->sendResponse();
        }
    }
}
