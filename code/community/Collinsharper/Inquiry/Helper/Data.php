<?php

class Collinsharper_Inquiry_Helper_Data extends Mage_Core_Helper_Abstract
{

    private function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    private function getQuoteId()
    {
        return Mage::getSingleton('checkout/session')->getQuoteId();
    }

    private function getQuoteTotal()
    {
        if(Mage::getSingleton('checkout/session')->getQuote()) {
            return round(Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal(),2);
        }

        return false;
    }

    private function getOrderId()
    {
        if(Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
           return  Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        if(Mage::getSingleton('checkout/session')->getQuote() &&
            Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId()) {
            return Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
        }

        return false;
    }

    public function log($val, $force = false)
    {
        if($force || Mage::getStoreConfig('chinquiry/chinquiry/debug_logging')) {
            Mage::log($val, null, 'ch_inquiry.log');
        }
    }

    public function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getId();
    }

    public function getUserEmail()
    {
        // TODO we could traverse the post object for any thing named "*email*" that matches a XX@XX.X
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            return $customer->getEmail();
        }

        if(isset($_POST['email'])) {
            return $_POST['email'];
        }

        if(isset($_POST['billing'])) {
            return $_POST['billing']['email'];
        }

        return false;
    }

    private function getIpAddress($long = true)
    {
        return Mage::helper('core/http')->getRemoteAddr($long);
    }

}
