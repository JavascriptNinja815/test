<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time Sweet Tooth spent
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension.
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Observer sales Order Invoice Pay
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Model_Observer_Block_Register extends Varien_Object {

    /**
     * Executed from the core_block_abstract_to_html_after event
     * @param Varien_Event $obj
     */
    public function afterOutput($obj) {
        $block = $obj->getEvent ()->getBlock ();
        $transport = $obj->getEvent ()->getTransport ();

        // Magento 1.3 and lower dont have this transport, so we can't do autointegration : (
        if(empty($transport)) {
            return $this;
        }

        $this->appendToSignupForm ( $block, $transport );
        $this->appendToOnepageCheckoutSignup ( $block, $transport );
        $this->appendToOnestepcheckoutSignup($block, $transport);

        return $this;
    }

    /**
     * Appends the points balance in the header somewhere
     * @param unknown_type $block
     * @param unknown_type $transport
     */
    public function appendToSignupForm($block, $transport) {

        if(!( $block instanceof Mage_Customer_Block_Form_Register )) {
            return $this;
        }

        if(!Mage::getStoreConfigFlag('rewards/autointegration/customer_register_referral_field')) {
            return $this;
        }

        $html = $transport->getHtml ();
        $st_html = $block->getChildHtml ( 'rewards_referral' );
        
        if (Mage::helper('rewards/theme')->getPackageName() == "enterprise") {
            $st_html = str_replace(array("\n", "\r"), '', $st_html);
        }
        
        // Check that content is not already integrated.
        if(!empty($st_html) && strpos($html, $st_html) === false) {
            // Find the correct HTML to integrate next to, otherwise the client should do a manual integration
            if(strpos($html, '<div class="buttons-set') !== false) {
                $button_set_begin_html = '<div class="buttons-set';
                $html = str_replace($button_set_begin_html, $st_html . $button_set_begin_html, $html);
            }
        }

        $transport->setHtml ( $html );
        return $this;
    }

    /**
     * Appends the points balance in the header somewhere
     * @param unknown_type $block
     * @param unknown_type $transport
     */
    public function appendToOnepageCheckoutSignup($block, $transport) {

        if(!( $block instanceof Mage_Checkout_Block_Onepage_Billing )) {
            return $this;
        }

        if(!Mage::getStoreConfigFlag('rewards/autointegration/onepage_billing_register_referral_field')) {
            return $this;
        }

        $html = $transport->getHtml ();
        $st_html = $block->getChildHtml ( 'rewards_referral_field' );

        $isEnterpriseVerion = Mage::helper('rewards/version')->isMageEnterprise();
        $isRwdTheme = Mage::helper('rewards/theme')->getPackageName() === "rwd";
        
        // Check that content is not already integrated.
        if(!empty($st_html) && strpos($html, $st_html) === false) {
            if(($isEnterpriseVerion || $isRwdTheme) && strpos($html, '<li class="fields" id="register-customer-password') !== false) {
                $html = $this->_appendToEnterpriseBillingAddressForm($html, $st_html);
            } else {
                $html = $this->_appendToBillingAddressForm($html, $st_html);
            }
        }

        $transport->setHtml ( $html );
        return $this;
    }

    /**
     *
     * @param unknown_type $orignal_html
     * @param unknown_type $st_html
     * @param unknown_type $transport
     */
    protected function _appendToEnterpriseBillingAddressForm($original_html, $st_html) {

        $pass_field_begin_html = '<li class="fields" id="register-customer-password';
        $pass_field_pos = strpos($original_html, $pass_field_begin_html);
        if($pass_field_pos === false) {
            // Could not find the correct HTML to integrate next to, so the client should do a manual integration
            return $original_html;
        }


        $fieldset_end_pos = strpos($original_html, '</li>', $pass_field_pos);
        if($fieldset_end_pos === false) {
            // Could not find the correct HTML to integrate next to, so the client should do a manual integration
            return $original_html;
        }
        $fieldset_end_pos_end = $fieldset_end_pos + strlen('</li>') + 1;

        $replace_html = substr($original_html, $fieldset_end_pos_end);

        return str_replace($replace_html, $st_html . $replace_html, $original_html);
    }

    /**
     *
     * @param unknown_type $orignal_html
     * @param unknown_type $st_html
     * @param unknown_type $transport
     */
    protected function _appendToBillingAddressForm($original_html, $st_html) {

        $billaddress_form_pos = strpos($original_html, '<li id="billing-new-address-form');
        if($billaddress_form_pos === false) {
            // Could not find the correct HTML to integrate next to, so the client should do a manual integration
            return $original_html;
        }


        $fieldset_end_pos = strpos($original_html, '</fieldset>', $billaddress_form_pos);
        if($fieldset_end_pos === false) {
            // Could not find the correct HTML to integrate next to, so the client should do a manual integration
            return $original_html;
        }
        $fieldset_end_pos_end = $fieldset_end_pos + strlen('</fieldset>') + 1;

        $replace_html = substr($original_html, $fieldset_end_pos_end);

        return str_replace($replace_html, $st_html . $replace_html, $original_html);
    }

    /**
     * Auto-integrates referral box into Idev Onestepcheckout page.
     * @param  [type] $block     [description]
     * @param  [type] $transport [description]
     * @return $this
     */
    public function appendToOnestepcheckoutSignup($block, $transport)
    {
        if (!$block instanceof Idev_OneStepCheckout_Block_Fields) {
            return $this;
        }

        if(!Mage::getStoreConfigFlag('rewards/autointegration/onepage_billing_register_referral_field')) {
            return $this;
        }

        $html   = $transport->getHtml();
        $stHtml = $block->getLayout()
            ->createBlock('rewardsref/field_checkout')
            ->setTemplate('rewardsref/onepage/field.phtml')
            ->toHtml();

        // Check that content is not already integrated.
        if(!empty($stHtml) && strpos($html, $stHtml) === false) {
            if (!Mage::helper('rewards/version')->isMageEnterprise()){
                // if not MEE we have to enclose ST field html into a <li class="clearfix"></li>
                if (preg_match('/<div.*?>([^`]*?)<\/div>/', $stHtml, $matches, PREG_OFFSET_CAPTURE)) {
                    $updatedStHtml = "<li class=\"clearfix\">" . $matches[0][0] . "</li>";
                    $stHtml = substr_replace($stHtml, $updatedStHtml, (int)$matches[0][1]);
                }
            } else {
                // if MCE we already have html in <li class="fields"></li>, so just replace class name
                $stHtml = str_replace('fields', 'clearfix', $stHtml);
            }

            $pos = '(<li\s.*\s*.*\s*(<input id="id_create_account"))';
            if (preg_match($pos, $html, $res, PREG_OFFSET_CAPTURE)) {
                $html = substr_replace($html, $stHtml, (int)$res[0][1], 0);
            }
        }

        $transport->setHtml($html);

        return $this;
    }
}