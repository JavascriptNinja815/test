<?php
/**
 * Collinsharper/Chcustomform/Helper/Data.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chinventorycache
 * @author    KL <support@collinsharper.com>
 * @copyright 2017 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Custom Form Extension Helper Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2017 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */

class Collinsharper_Chcustomform_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MAIL_AUTO_INC_NUMBER = 'chcustomform/chcustomform_group/mail_init_num';
    
    const RECAPTCHA_ENABLE = 'chcustomform/chcustomform_group/grecaptcha_enable';
    
    const RECAPTCHA_SITE_KEY = 'chcustomform/chcustomform_group/grecaptcha_site_key';

    const RECAPTCHA_SECRET_KEY = 'chcustomform/chcustomform_group/grecaptcha_secret_key';

    const RECAPTCHA_VERIFIER = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Generic Debug Log function
     *
     * @param string|null $_message Debug Message
     * @param int|null    $_level   Magento Log level
     *
     * @return  void
     */
    public function debugLog($_message, $_level = null)
    {
        Mage::log($_message, $_level, "ch_customform.log");
    }

    /**
     * Retrieve Customer Object
     *
     * @return  Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     * Get Customer ID
     *
     * @return  int
     */
    public function getCustomerId()
    {
        // Try to return the active customer
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getCustomer()->getId();
        } else if (Mage::registry('current_customer')) {
            return Mage::registry('current_customer')->getId();
        }

    }

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    public function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
    /*
     * Code for retrieving auto increment number for mail
     */
    public function getMailAutoIncNumber()
    {
        return Mage::getStoreConfig(self::MAIL_AUTO_INC_NUMBER, Mage::app()->getStore()->getId());
    }
    /*
     * Code for retreiving value of Google reCaptcha enable field from admin panel
     */
    public function getGoogleRecaptchaEnable()
    {
        return Mage::getStoreConfig(self::RECAPTCHA_ENABLE, Mage::app()->getStore()->getId());
    }
    /*
     * Code for retreiving value of Google reCaptcha site key field from admin panel
     */
    public function getGoogleRecaptchaSiteKey()
    {
        return Mage::getStoreConfig(self::RECAPTCHA_SITE_KEY, Mage::app()->getStore()->getId());
    }
    /*
     * Code for retreiving value of Google reCaptcha secret key field from admin panel
     */
    public function getGoogleRecaptchaSecretKey()
    {
        return Mage::getStoreConfig(self::RECAPTCHA_SECRET_KEY, Mage::app()->getStore()->getId());
    }
    /*
     * Code of Google reCaptcha verification url
     */
    public function getGoogleRecaptchaVerifyUrl()
    {
        return self::RECAPTCHA_VERIFIER;
    }
    /*
     * Code for verifying Google reCaptcha value
     */
    public function validateGoogleRecaptcha($response)
    {
        $ch = curl_init();
        $postFields = array(
            'secret' => $this->getGoogleRecaptchaSecretKey(),
            'response' => $response
        );
        curl_setopt($ch, CURLOPT_URL, $this->getGoogleRecaptchaVerifyUrl());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close ($ch);
        $data = json_decode($output);
        if(!empty($data) && $data->success && $data->success===true) {
            return true;
        } 
        return false; 
    }
    
}
