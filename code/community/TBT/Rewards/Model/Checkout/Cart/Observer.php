<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

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
 * An observer that observs cart changes and determines if the customer will have enough points
 * to checkout.  If not, a message is displayed.
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Checkout_Cart_Observer {
	
	/**
	 * observers controller_action_postdispatch for Mage_Checkout_CartController
         * then add messages if the layout has not loaded
         * 
	 * @return  TBT_Rewards_Model_Checkout_Cart_Observer
	 */
	public function checkRedemptions($observer) {
            if($observer['controller_action'] instanceof Mage_Checkout_CartController) {
                /* @var Mage_Checkout_CartController */
                $cartController = $observer['controller_action'];
                /* @var Mage_Core_Model_Layout */
                $cartLayoutBlocks = $cartController->getLayout()->getAllBlocks();
                // only add session messages before the layout has been loaded
                if(empty($cartLayoutBlocks)) {
                    if ($this->_getRewardsSess ()->isCustomerLoggedIn ()) {
                            if ($this->_getRewardsSess ()->isCartOverspent ()) {
                                $session = Mage::getSingleton('rewards/session');
                                
                                $pointsSpending = $session->getTotalPointsSpending();
                                $pointsSpending = (array_key_exists(1, $pointsSpending)) ? $pointsSpending[1] : 0;

                                $pointsAvailable = 0;
                                $customerId = $session->getCustomerSession()->getCustomerId();

                                if ($customerId) {
                                    $customer = Mage::getModel('rewards/customer')->load($customerId);
                                    $pointsAvailable = $customer->getUsablePointsBalance(1);
                                }

                                $errors = Mage::helper('rewards')->__("You are trying to spend %s points on this order but you only have %s points available. You will not be able to checkout.", $pointsSpending, $pointsAvailable);
                                $this->_getCheckoutSession()->addError($errors);
                            }
                    } else {
                            if ($this->_getRewardsSess ()->hasRedemptions ()) {
                                    $msg = Mage::helper ( 'rewards' )->__ ( "You are trying to redeem points but you're not logged in. You may not have enough points to checkout." );
                                    $this->_getCheckoutSession ()->addError ( $msg );
                            }
                    }
                }
            }
            return $this;
	}
	
	/**
	 * Fetches the customer rewards session.
	 *
	 * @return TBT_Rewards_Model_Session
	 */
	protected function _getRewardsSess() {
		return Mage::getSingleton ( 'rewards/session' );
	}
	
	/**
	 * Fetches the checkout session
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckoutSession() {
		return Mage::getSingleton ( 'checkout/session' );
	}
        
    /**
     * @event controller_action_predispatch_onestepcheckout_index_index
     */
    public function oneStepCheckoutPreDispatch($e)
    {
        if (Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.6.0.0")) {
            $this->_getCheckoutSession()->getQuote()->collectTotals();
        }
    }
    
    /**
     * @event checkout_controller_onepage_save_shipping_method
     */
    public function onepageSaveShippingMethod($e)
    {
        if (!Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.6.0.0") && Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
            $this->_getCheckoutSession()->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
        }
    }
}
