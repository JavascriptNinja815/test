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
 * Product View Points
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Product_Price extends Mage_Catalog_Block_Product_Price
{
	protected $_product = null;
	
	/**
	 * Retrieve current product model
	 *
	 * @return TBT_Rewards_Model_Catalog_Product
	 */
	public function getProduct() {
		if (! $this->isEnabled ()) {
			return parent::getProduct ();
		}
		if ($this->_product == null) {
			
			$p = $this->_getData ( 'product' );
			if (empty ( $p )) {
				$p = Mage::registry ( 'product' );
			}
			if (! empty ( $p )) {
				if ($p instanceof TBT_Rewards_Model_Catalog_Product) {
					$this->_product = $p;
				} else {
					$this->_product = Mage::getModel ( 'rewards/catalog_product' )->load ( $p->getId () );
				}
				Mage::unregister ( 'product' );
				Mage::register ( 'product', $this->_product );
			} else {
				$this->_product = Mage::getModel ( 'rewards/catalog_product' );
			}
		}
		return $this->_product;
	}
	
	/**
	 * Override this method in descendants to produce html
	 *
	 * @return string
	 */
	protected function _toHtml() {
		if ($this->isEnabled ()) {
			$this->setTemplate ( 'rewards/product/price.phtml' );
		}
		return parent::_toHtml ();
	}
	
	/**
	 * Fetches a string value of this product
	 *
	 * @return string
	 */
	public function getSimplePointsCost() {
		
		$product = $this->getProduct ();
		$rule_selector = $this->getRedemptionSelectionAlgorithm ();
		$rule_selector->init ( $this->getCurrentCustomer (), $this->getProduct () );

                if (! $rule_selector->hasRule ()) {
			return $this->getDefaultMessage ();
		}
		
		$rule = $rule_selector->getRule ();
		$points_str = $rule->getPointsString ( $product );
		
		return $points_str;
	}
	
	public function getDefaultMessage() {
		return $this->__ ( "See product details" );
	}
	
	/**
	 * Loads the rule selection algorithm model from config.
	 *
	 * @return TBT_Rewards_Model_Catalogrule_Selection_Algorithm_Abstract
	 */
	private function getRedemptionSelectionAlgorithm() {
		return Mage::helper ( 'rewards/config' )->getRedemptionSelectionAlgorithm ();
	}
	
	/**
	 * TODO: This doesn't really disable the module, we need to extend the
	 * correct class and abstract out the getRedeemableOptions() function.
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		$show_points_as_price = Mage::helper ( 'rewards/config' )->showPointsAsPrice ();
		return $show_points_as_price;
	}
	
	/**
	 * Fetches any points redeemable options.
	 *
	 * @param Mage_Catalog_Model_Product $product [ = null ]
	 * @return array()
	 */
	public function getRedeemableOptions() 
        {
            Varien_Profiler::start('TBT_Rewards:: Get Redeemable Options');
            $applicableRules = array();
            $product = $this->getProduct();

            try {
                $applicableRules = $product->getRedeemableOptions($this->getCurrentCustomer(), $product);
            } catch (Exception $e) {
                $message = "An error occurred trying to apply the redemption while adding the product to your cart: " . $e->getMessage();
                Mage::helper('rewards')->log($message);
            }
            
            Varien_Profiler::stop('TBT_Rewards:: Get Redeemable Options');
            return $applicableRules;
	}
	
	/**
	 * Fetches the current session customer, or false if the customer is not logged in.
	 *
	 * @return TBT_Rewards_Model_Customer|false
	 */
	public function getCurrentCustomer() {
		if ($this->customer == null) {
			if ($this->_getRS ()->isCustomerLoggedIn ()) {
				$this->customer = Mage::getModel('rewards/customer')->getRewardsCustomer($this->_getRS()->getSessionCustomer());
				if (! $this->customer->getId ()) {
					$this->customer = false;
				}
			} else {
				$this->customer = false;
			}
		}
		return $this->customer;
	}
	
	/**
	 * Fetches a catalogrule
	 *
	 * @param integer $rule_id
	 * @return TBT_Rewards_Model_Catalogrule_Rule
	 */
	protected function _getRule($rule_id) {
		$rule = Mage::getModel ( 'rewards/catalogrule_rule' )->load ( $rule_id );
		return $rule;
	}
	
	/**
	 * Fetches the rewards session model
	 *
	 * @return TBT_Rewards_Model_Session
	 */
	protected function _getRewardsSess() {
		return Mage::getSingleton ( 'rewards/session' );
	}
	
	/**
	 * Fetches the rewards session model
	 * alias to _getRewardsSess()
	 *
	 * @return TBT_Rewards_Model_Session
	 */
	protected function _getRS() {
		return $this->_getRewardsSess ();
	}
}

