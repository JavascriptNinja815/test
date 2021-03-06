<?php 
/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL: 
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL: 
 *      http://opensource.org/licenses/osl-3.0.php
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
 * ONE page checkout progress sidebox that tells the customer how many points they
 * are spending/earning
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPlat_Block_Checkout_Onepage_Link extends Mage_Checkout_Block_Onepage_Link
{
    protected function _construct()
    {
        parent::_construct();
    }
	
    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml()
    {
    	if(!$this->canCheckout() && $this->disableCheckoutsIfNotEnoughtPoints())	{
        	$this->setTemplate('rewardsplat/checkout/onepage/link.phtml');
    	}
        return parent::_toHtml();
    }
    
    public function disableCheckoutsIfNotEnoughtPoints() {
    	return Mage::helper('rewardsplat/config')->disableCheckoutsIfNotEnoughPoints();
    }
    
    public function getBilling()
    {
        return $this->getQuote()->getBillingAddress();
    }

    public function getShipping()
    {
        return $this->getQuote()->getShippingAddress();
    }

    public function getShippingMethod()
    {
        return $this->getQuote()->getShippingAddress()->getShippingMethod();
    }

    public function getShippingDescription()
    {
        return $this->getQuote()->getShippingAddress()->getShippingDescription();
    }

    public function getShippingAmount()
    {
        /*$amount = $this->getQuote()->getShippingAddress()->getShippingAmount();
        $filter = Mage::app()->getStore()->getPriceFilter();
        return $filter->filter($amount);*/
        //return $this->helper('checkout')->formatPrice(
        //    $this->getQuote()->getShippingAddress()->getShippingAmount()
        //);
        return $this->getQuote()->getShippingAddress()->getShippingAmount();
    }

    public function getShippingPriceInclTax()
    {
        $exclTax = $this->getQuote()->getShippingAddress()->getShippingAmount();
        $taxAmount = $this->getQuote()->getShippingAddress()->getShippingTaxAmount();
        return $this->formatPrice($exclTax + $taxAmount);
    }

    public function getShippingPriceExclTax()
    {
        return $this->formatPrice($this->getQuote()->getShippingAddress()->getShippingAmount());
    }

    public function formatPrice($price)
    {
        return $this->getQuote()->getStore()->formatPrice($price);
    }
    
    public function getTotalPointsSpending() {
    	$spending = $this->_getRewardsSess()->getTotalPointsSpendingAsString();
        return $spending;
    }
    
    public function getTotalPointsEarning() {
    	$spending = $this->_getRewardsSess()->getTotalPointsEarningAsString();
        return $spending;
    }
    
    /**
     * True if the customer can checkout given the current cart redemptions, false otherwise.
     * 
     * @return  TBT_Rewards_Model_Checkout_Cart_Observer
     */
    public function canCheckout() {
   	    //  die(print_r($points_spent, true));
   	    $customer_is_logged_in = $this->_getRewardsSess()->isCustomerLoggedIn();
    	if($customer_is_logged_in) {
    		$is_cart_overspent = $this->_getRewardsSess()->isCartOverspent();
	        if($is_cart_overspent) {
	        	return false;
	        }
    	} else {
    		$has_redemptions = $this->_getRewardsSess()->hasRedemptions();
    		$cant_use_redemptions_if_not_logged_in = !Mage::helper('rewardsplat/config')->canUseRedemptionsIfNotLoggedIn();
    		if($has_redemptions && $cant_use_redemptions_if_not_logged_in) {
	        	return false;
    		}
    	}
        return true;
    }

    public function isLoggedIn() {
    	return Mage::getSingleton('rewards/session')->isCustomerLoggedIn();
    }
    
    /**
     * Fetches the customer session singleton
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSess() {
    	return Mage::getSingleton('rewards/session');
    }
}