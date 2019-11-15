<?php

/**
 * Collinsharper/Chcustomform/Block/Renderer.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustomform
 * @author    KL <support@collinsharper.com>
 * @copyright 2017 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */

/**
 * Collinsharper Custom Form Extension Block Renderer Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chaddtocart
 * @author    KL <support@collinsharper.com>
 * @copyright 2017 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Chcustomform_Block_Renderer extends Mage_Core_Block_Template {
    /*
     * Code for showing html data
     */
    public function _toHtml() {
        $this->setTemplate('chcustomform/chcustom_form.phtml');
        return parent::_toHtml();
    }
    /*
     * Code for encryption of mailto data
     */
    public function getEncryptData() {
        $encrypted_data = Mage::helper('core')->encrypt($this->getMailto());
        return $encrypted_data;
    }
    /*
     * Code for auto increment value of mail
     */
    public function getAutoIncrementValue() {
        $autoIncrementVal = $this->getAutoIncrementNum();
        return $autoIncrementVal;
    }
    /*
     * Code for getting static block id
     */
    public function getBlockId() {
        $staticBlockId = $this->getStaticBlockId();
        return $staticBlockId;
    }
    /*
     * Code for getting static block title
     */
    public function getBlockTitle() {
        $getId = $this->getBlockId();
        $getTitle = Mage::getModel('cms/block')->load($getId)->getTitle();
        return $getTitle;
    }
    /*
     * Code for rendering static block data
     */
    public function getContentHtml() {
        try {
            $staticBlockId = $this->getBlockId();
            $captchaTag = "<!--[[ CHCUSTOMFORM_RECAPTCHA]]-->";
            $formHtml = $this->getLayout()->createBlock('cms/block')->setBlockId($staticBlockId)->toHtml();
            if (strpos($formHtml, '<form') === false || strpos($formHtml, '</form>') === false) {
                Mage::throwException('Missing form element.');
            }
            if (strpos($formHtml, $captchaTag) !== false) {
                $helper = Mage::helper('chcustomform');
                $gCaptchaEnable = $helper->getGoogleRecaptchaEnable();
                $siteKey = $helper->getGoogleRecaptchaSiteKey();
                $captchaDiv = "<div class='g-recaptcha' data-sitekey=" . $siteKey . "></div>";
                if ($gCaptchaEnable == 1) {
                    $formHtml = str_ireplace($captchaTag, $captchaDiv, $formHtml);
                }
            }
            return $formHtml;
        } catch (Exception $ex) {
            echo 'Message: ' . $ex->getMessage();
        }
    }

}
