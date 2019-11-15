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
 * Product View Points
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Product_View_Points_Abstract extends Mage_Core_Block_Template 
{

	protected $applicable_rules_map = null;
	protected $customer = null;
	protected $_product = null;

	/**
	 * Ensures that the given product model is a rewards catlaog product
	 *
	 * @param mixed $product
	 * @return TBT_Rewards_Model_Catalog_Product
	 */
	private function ensureProduct($product) {
		if ($product == null) {
			$product = $this->getProduct ();
		}
		if ($product instanceof Mage_Catalog_Model_Product) {
			$product = TBT_Rewards_Model_Catalog_Product::wrap ( $product );
		}
		return $product;
	}

	/**
	 * Fetches the current session customer, or false if the customer is not logged in.
	 *
	 * @return TBT_Rewards_Model_Customer the customer model or false if no customer is logged in.
	 */
	public function getCurrentCustomer() {
		if ($this->customer == null) {
			if ($this->_getRS ()->isCustomerLoggedIn ()) {
				$this->customer = $this->_getRS ()->getSessionCustomer ();
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
	 * Fetches the final price for this product formatted to the correct store currency.
	 *
	 * @return string
	 */
	public function getOriginalPrice() {
		return Mage::helper ( 'core' )->formatCurrency ( $this->getProduct ()->getFinalPrice (), false );
	}

	/**
	 * Retrieve current product model
	 *
	 * @return TBT_Rewards_Model_Catalog_Product
	 */
	public function getProduct() {
		if ($this->_product == null) {
			$p = Mage::registry ( 'product' );
			if (empty ( $p ))
				$p = $this->getData('product');

			if ($p) {
				if ($p instanceof TBT_Rewards_Model_Catalog_Product) {
					$this->_product = $p;
				} else {
					$this->_product = Mage::getModel ( 'rewards/catalog_product' )->setStoreId ( $p->getStoreId () )->load ( $p->getId () );
					$this->_product->addData ( $p->getData () );

                    $requestParams = Mage::app()->getRequest()->getParams();
                    $buyRequest = $this->_getProductRequest($requestParams);

                    if ($this->_hasProductOptionsInRequest($buyRequest)) {
                        $this->_product->getTypeInstance(false)->prepareForCartAdvanced($buyRequest, $this->_product);
                    }
                }
			} else {
				$this->_product = Mage::getModel ( 'rewards/catalog_product' );
			}
			Mage::unregister ( 'product' );
			Mage::register ( 'product', $this->_product );
		}
		return $this->_product;
	}

    /**
	 * Get request for product add to cart procedure
	 *
	 * @param   mixed $requestInfo
	 * @return  Varien_Object
	 */
    protected function _getProductRequest($requestInfo)
    {
        if ($requestInfo instanceof Varien_Object) {
            $request = $requestInfo;
		} elseif (is_numeric($requestInfo)) {
			$request = new Varien_Object();
			$request->setQty($requestInfo);
		} else {
			$request = new Varien_Object($requestInfo);
		}

		if (!$request->hasQty()) {
			$request->setQty(1);
		}
		return $request;
	}

    /**
     * Check to see if Product Options exist in request
     * 
     * @param Varien_Object|array $buyRequest
     * @return boolean
     */
    protected function _hasProductOptionsInRequest($buyRequest)
    {
        $requestParams = $buyRequest;

        if ($buyRequest instanceof Varien_Object) {
            $requestParams = $buyRequest->getData();
        }

        $paramNames = array_keys($requestParams);
        $matchExpr = '/option|link|gift|attribute/i';

        if (preg_match($matchExpr, implode(',',$paramNames))) {
            return true;
        }

        return false;
    }
        
	/**
         * TODO: CHECK CONFIG FOR IF CUSTOMER WANTS TO DISPLAY POINTS IMAGE
	 * TODO: ^^^^^^^^ already done?
	 *
	 * @param integer $num_points
	 * @param integer $currency_id
	 * @return string
	 */
	public function getPointsImgUrl($num_points, $currency_id) 
    {
        if ($num_points > 0) {
            $params = array(
                'quantity' => $num_points,
                'currency' => $currency_id,
                '_area' => 'frontend'
            );
            $url = $this->getUrl('rewards/image/', $params);

            if (!@is_array(getimagesize($url))) {
                return "";
            }

            return $url;
        } else {
            return "";
        }
	}

	/**
	 * Returns true if you should show the redeemer for this product.
	 *
	 * @return boolean
	 */
	public function doShowRedeemer() {
		$loggedIn = $this->_getRS ()->isCustomerLoggedIn ();
		$showWhenNotLoggedIn = Mage::helper ( 'rewards/config' )->showRedeemerWhenNotLoggedIn ();
		$show = ($showWhenNotLoggedIn || $loggedIn) && $this->hasRedemptionOptions ();
                return $show;
	}

	/**
	 * Fetches any points redeemable options.
	 *
	 * @param Mage_Catalog_Model_Product $product [ = null ]
	 * @return array()
	 */
	public function getRedeemableOptions($target_product = null) {
		Varien_Profiler::start ( 'TBT_Rewards:: Get Redeemable Options' );
		$applicable_rules = array ();
		$this->applicable_rules_map = array ();
		$product = $this->ensureProduct ( $target_product );
                $store = $this->_getAggregatedCart()->getStore();
                
		try {
                        $gId = $this->_getAggregatedCart()->getCustomerGroupId();
			$applicable_rules = $product->getCatalogRedemptionRules ( $this->getCurrentCustomer () );
			foreach ( $applicable_rules as $i => &$arr ) {
				$arr = ( array ) $arr;
				$rule_id = $arr [TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID];
				$rule = Mage::getModel ( 'rewards/catalogrule_rule' )->load ( $rule_id );
				if (! $rule->getId ()) {
					unset ( $applicable_rules [$i] );
					continue;
				}

				// create a fake item using our product
				$points = $product->getCatalogPointsForRule ( $rule_id );
				if (! $points) {
					unset ( $applicable_rules [$i] );
					continue;
				}

				$currency_id = $arr [TBT_Rewards_Model_Catalogrule_Rule::POINTS_CURRENCY_ID];
				//				$amt = $arr[TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT];
				$amt = $points ['amount'] * - 1;
				$effect = $arr [TBT_Rewards_Model_Catalogrule_Rule::POINTS_EFFECT];

				$arr ['caption'] = $rule->getName ();
				$arr ['points'] = Mage::helper ( 'rewards/currency' )->formatCurrency ( $amt, $currency_id );
				$arr ['points_caption'] = Mage::helper ( 'rewards' )->getPointsString ( array ($currency_id => $amt ) );
				$arr ['amount'] = $amt;
				$arr ['currency_id'] = $currency_id;
                                $arr ['points_action'] = $rule->getPointsAction();
				//@nelkaake 15/01/2010 6:45:20 PM : added flag to NOT round the price in case points
				// are worth less than the current currency precision for the particular view.
				$arr ['new_price'] = Mage::helper ( 'rewards' )->priceAdjuster ( $product->getFinalPrice (), $effect, false, false );

				$price_disp_base = $product->getFinalPrice () - ( float ) $arr ['new_price'];
				$price_disp_base = $store->getBaseCurrency ()->convert ( $price_disp_base, $store->getCurrentCurrency () );
				$arr ['price_disposition'] = $price_disp_base;
                                
                                /* 
                                 * If we are dealing with a bundle product and we have a "to fixed price" type of discount 
                                 * we are going to have a price disposition below 0 because the initial price of the bundle product is 0
                                 * This would make the slider remain hidden no matter what, so we set it to 0
                                 */
                                if ($arr ['price_disposition'] < 0) {
                                    $arr ['price_disposition'] = 0;
                                }
                                
                                $arr ['effect'] = $effect;
                                
                                // Include Monetary step for "Deduct By Amount Spent" discounts
                                if ($arr ['points_action'] == TBT_Rewards_Model_Catalogrule_Actions::DEDUCT_BY_AMOUNT_SPENT_ACTION) {
                                    $arr ['monetary_step'] = $rule->getPointsAmountStep();
                                }
                                
				$arr ['new_price'] = Mage::helper ( 'core' )->formatCurrency ( $arr ['new_price'], false );
				$arr ['max_uses'] = $rule->getPointsUsesPerProduct ();

				$customer = $this->getCurrentCustomer ();
				if ($customer) {
					$arr ['can_use_rule'] = true;
				} else {
					$arr ['can_use_rule'] = Mage::helper ( 'rewards/config' )->canUseRedemptionsIfNotLoggedIn ();
				}

				$this->applicable_rules_map [$rule->getId ()] = $arr;
			}

		} catch ( Exception $e ) {
                    $message = Mage::helper('rewards')->__("An error occurred trying to apply the redemption while adding the product to your cart: " . $e->getMessage ());
                    Mage::getSingleton('core/session')->addError($message);
                    Mage::helper('rewards/debug')->log($message);
		}
                
		Varien_Profiler::stop ( 'TBT_Rewards:: Get Redeemable Options' );
		return $applicable_rules;
	}

	/**
	 * Returns an array of the applicable redemption rules for this product
	 *
	 * @return array map
	 */
	public function getApplicableRulesMap() {
		if ($this->applicable_rules_map == null) {
			$this->getRedeemableOptions ();
		}
		return $this->applicable_rules_map;
	}

	/**
	 * Returns an array/map of the applicable redemption rules and settings for this product
	 *
	 * @return array $ruleMap
	 */
	public function getRuleSettingsMap() {
		$store = Mage::app ()->getStore ();
		$product = $this->ensureProduct ( $this->getProduct () );
		$customer = $this->getCurrentCustomer ();
		$price = ( float ) $product->getFinalPrice ();
		$ruleMap = array ();
		$ruleMap [$product->getId ()] = array ();
		$ruleMap [$product->getId ()] [( string ) $price] = array ();
		$applicable_rules = $product->getCatalogRedemptionRules ( $customer );
		foreach ( $applicable_rules as $i => &$arr ) {
			$arr = ( array ) $arr;
			$rule_id = $arr [TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID];
			$rule = Mage::getModel ( 'rewards/catalogrule_rule' )->load ( $rule_id );
			if ($rule->getId ()) {
				$ruleMap [$product->getId ()] [( string ) $price] [$rule->getId ()] = $rule->getPointSliderSettings ( $store, $product, $customer, $price );
			}
		}
		return $ruleMap;
	}

	/**
	 * True if any redeption options exist for this product.
	 *
	 * @return boolean
	 */
	public function hasRedemptionOptions() {
		$ruleOptions = $this->getRedeemableOptions ();
		$hasRedemptionRules = sizeof ( $ruleOptions ) > 0;
		return $hasRedemptionRules;
	}

	public function getPointsString($qty, $currency_id) {
		return Mage::helper ( 'rewards' )->getPointsString ( array ($currency_id => $qty ) );
	}

	public function getCurrencyMapJson() {
		$currencies = Mage::helper ( 'rewards/currency' )->getAvailCurrencies ();
		return json_encode ( $currencies );
	}

	public function getDefaultGuestPoints() {
		return Mage::helper ( 'rewards/config' )->getSimulatedPointMax ();
	}

	public function doGraphicalEarning() {
		return Mage::getStoreConfigFlag ( 'rewards/display/showEarningGraphic' );
	}

	/**
	 * Fetches the rewards session singleton
	 *
	 * @return TBT_Rewards_Model_Session
	 */
	protected function _getRS() {
		return Mage::getSingleton ( 'rewards/session' );
	}
        
        protected function _getAggregatedCart()
        {
            return Mage::getSingleton('rewards/sales_aggregated_cart');
        }

}
