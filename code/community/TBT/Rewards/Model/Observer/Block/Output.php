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
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Block_Output extends Varien_Object
{

    /**
     * Add slider to firecheckout page
     * 
     * @param Varien_Event $e
     * @event controller_action_layout_render_before_firecheckout_index_index
     */
    public function addSliderToFirecheckout($e)
    {
        $layout = Mage::app()->getLayout();
        
        $additionalDetailsBlock = $layout->createBlock('core/template', 'additional.spender.js.before')
            ->setTemplate('rewards/spender/additional/firecheckout.phtml');
        
        $sliderBlock = $layout->createBlock('rewards/spender_combined', 'points.spender')
            ->setData('additional_selectors', 'checkout-points-spender firecheckout-points-spender')
            ->append($additionalDetailsBlock);
        
        $layout->getBlock('checkout.additional.information')
            ->append($sliderBlock);
    }
    
    /**
     * Executed from the core_block_abstract_to_html_after event
     * @param Varien_Event $obj
     */
    public function afterOutput($obj)
    {
        $block = $obj->getEvent ()->getBlock ();
        $transport = $obj->getEvent ()->getTransport ();

        // Magento 1.3 and lower dont have this transport, so we can't do autointegration : (
        if(empty($transport)) {
            return $this;
        }

        if (Mage::getStoreConfigFlag('advanced/modules_disable_output/TBT_Rewards')) {
            return $this;
        }

        $this->appendBirthdayPredictPoints ( $block, $transport );
        $this->overwriteCheckoutButtons ( $block, $transport );

        return $this;
    }

    public function appendBirthdayPredictPoints($block, $transport)
    {

        if (!Mage::getStoreConfigFlag('rewards/autointegration/predict_birthday_points')) {
            return $this;
        }

        // Check if Block is Dob Block
        if (!( $block instanceof Mage_Customer_Block_Widget_Dob )) {
            return $this;
        }
        if (Mage::getSingleton('rewards/session')->isCustomerLoggedIn()) {
            return $this;
        }

        $html = $transport->getHtml ();
        $st_html = $block->getLayout()->createBlock('rewards/special_birthday')->toHtml();

        // Check that content is not already integrated.
        if ( $st_html != "" && strpos($html, $st_html) === false ) {
            $html .= $st_html;
        }

        $transport->setHtml($html);

        return $this;

    }

    /**
     * Overwrites the various checkout buttons if the customer shouldn't be able to checkout (based on
     * redemptions) with an appropriate message telling the customer what to do.
     * @param Mage_Core_Block_Template $block
     * @param Varien_Object $transport
     */
    public function overwriteCheckoutButtons($block, $transport)
    {
        if (!($block instanceof Mage_Checkout_Block_Onepage_Link) &&
                !($block instanceof Mage_Checkout_Block_Multishipping_Link) &&
                !($block instanceof Mage_Paypal_Block_Express_Shortcut)) {

            return $this;
        }

        if ($this->_getRewardsSession()->canCheckoutWithCurrentRedemptions()) {
            return $this;
        }

        if (!$this->_disableCheckoutsIfNotEnoughPoints()) {
            return $this;
        }

        switch(get_class($block)) {
            case 'Mage_Checkout_Block_Onepage_Link':
                $this->_overwriteMainCheckoutButton($block, $transport);
                break;
            case 'Idev_OneStepCheckout_Block_Checkout_Onepage_Link':
                $this->_overwriteMainCheckoutButton($block, $transport);
                break;
            case 'Mage_Checkout_Block_Multishipping_Link':
                $this->_removeMultishippingLink($block, $transport);
                break;
            case 'Mage_Paypal_Block_Express_Shortcut':
                $this->_removePaypalExpressButton($block, $transport);
                break;
        }

        return $this;
    }

    /**
     * Overwrites the cart's checkout button with a "not enough points" message if the customer
     * doesn't have enough points to checkout with their specified redemptions, or a "you must login to
     * spend points" message if the customer is trying to spend points as a guest.
     * @param Mage_Checkout_Block_Onepage_Link $mageBlock
     * @param Varien_Object $transport
     */
    protected function _overwriteMainCheckoutButton($mageBlock, $transport)
    {
        if (!($mageBlock instanceof Mage_Checkout_Block_Onepage_Link || $mageBlock instanceof Idev_OneStepCheckout_Block_Checkout_Onepage_Link)) {
            return $this;
        }

        if ($mageBlock instanceof TBT_Rewards_Block_Checkout_Onepage_Link) {
            return $this;
        }

        $rewardsBlock = $mageBlock->getLayout()->createBlock('rewards/checkout_onepage_link');
        $transport->setHtml($rewardsBlock->toHtml());

        return $this;
    }

    /**
     * Removes the cart's multishipping checkout link (assumes the checkout button is being overwritten)
     * @param Mage_Checkout_Block_Multishipping_Link $block
     * @param Varien_Object $transport
     */
    protected function _removeMultishippingLink($block, $transport)
    {
        if (!($block instanceof Mage_Checkout_Block_Multishipping_Link)) {
            return $this;
        }

        $transport->setHtml('');

        return $this;
    }

    /**
     * Removes the cart's PayPal Express Checkout button (assumes the regular checkout button is being overwritten)
     * @param Mage_Paypal_Block_Express_Shortcut $block
     * @param Varien_Object $transport
     */
    protected function _removePaypalExpressButton($block, $transport)
    {
        if (!($block instanceof Mage_Paypal_Block_Express_Shortcut)) {
            return $this;
        }

        $transport->setHtml('');

        return $this;
    }

    /**
     * @deprecated Misspelled method name
     * @see _disableCheckoutsIfNotEnoughPoints
     */
    protected function _disableCheckoutsIfNotEnoughtPoints() {
        return Mage::helper('rewards/config')->disableCheckoutsIfNotEnoughPoints();
    }

    protected function _disableCheckoutsIfNotEnoughPoints()
    {
        return Mage::helper('rewards/config')->disableCheckoutsIfNotEnoughPoints();
    }

    /**
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton('rewards/session');
    }
}
