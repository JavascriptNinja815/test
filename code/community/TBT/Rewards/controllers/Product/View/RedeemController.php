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
 * Image Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Product_View_RedeemController extends Mage_Core_Controller_Front_Action {
	
	/**
	 * Fetches a configurable product requested by the user.
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return Mage_Catalog_Model_Product_Type_Configurable
	 */
	private function _initConfigurableProduct(Mage_Catalog_Model_Product $product = null) {
		if ($product == null) {
			$product = $this->_initProduct ();
		}
		if (! $product->isComposite ()) {
			throw new Mage_Core_Exception ( "Not a configurable product.", self::EC_NOT_CONFIGURABLE );
		} else {
			$product = $product->getTypeInstance ( false );
			if ($product instanceof Mage_Catalog_Model_Product_Type_Configurable) {
			
			} else {
				throw new Mage_Core_Exception ( "Not a configurable product.", self::EC_NOT_CONFIGURABLE );
			}
		}
		return $product;
	}
	
	/**
	 * Get request for product add to cart procedure
	 *
	 * @param   mixed $requestInfo
	 * @return  Varien_Object
	 */
	protected function _getProductRequest($requestInfo) {
		if ($requestInfo instanceof Varien_Object) {
			$request = $requestInfo;
		} elseif (is_numeric ( $requestInfo )) {
			$request = new Varien_Object ();
			$request->setQty ( $requestInfo );
		} else {
			$request = new Varien_Object ( $requestInfo );
		}
		
		if (! $request->hasQty ()) {
			$request->setQty ( 1 );
		}
		return $request;
	}
	
	/**
	 * Loads a product requested by the customer
	 *
	 * @throws Mage_Core_Exception
	 * @return Mage_Catalog_Model_Product
	 */
	private function &_initProduct() {
		if ($pid = $this->getRequest ()->get ( "product" )) {
			$product = Mage::getModel ( 'catalog/product' )->setStoreId ( Mage::app ()->getStore ()->getId () )->load ( $pid );
			if (! $product->getId ()) {
				throw new Mage_Core_Exception ( "Product ID provided does not exist", self::EC_BAD_PID );
			}
			$params = $this->getRequest ()->getParams ();
			$request = $this->_getProductRequest ( $params );
			if ($product->isConfigurable ()) {
				$product->getTypeInstance ( true )->prepareForCart ( $request, $product );
			}
			return $product;
		} else {
			throw new Mage_Core_Exception ( "No product ID provided.", self::EC_NO_PID );
		}
	}
	
	/**
	 * AJAX: Echos a redeemed product price given a redemption rule and product id.
	 *
	 */
	public function redPriceAction() 
        {
            try {
                $product = $this->_initProduct();
                $str = $product->getFinalPrice();
                $this->getResponse()->setBody($str);
            } catch ( Mage_Core_Exception $e ) {
                $this->getResponse()->setBody("Error: " . $e->getMessage());
            }
	}
	
	/**
	 * AJAX CALL. return a list of rules given a product and product price
	 * param product_id
	 * param rule_id
	 * param price
	 */
	public function getProductPriceRuleSettingsAction()
        {
            try {
                $store = Mage::app ()->getStore ();
                $productId = $this->getRequest ()->get("productId");
                $product = Mage::getModel ( 'catalog/product' )->setStoreId ( $store->getId () )->load ( $productId );
                
                if (!$product->getId()) {
                    throw new Exception ( "Product ID provided does not exist" );
                }
                
                if ($product instanceof Mage_Catalog_Model_Product) {
                    $product = TBT_Rewards_Model_Catalog_Product::wrap ( $product );
                }

                $customer = false;
                if (Mage::getSingleton ( 'rewards/session' )->isCustomerLoggedIn ()) {
                    $customer = Mage::getSingleton ( 'rewards/session' )->getCustomer ();
                }

                $price = ( float ) $this->getRequest ()->get ( "price" );
                $productPriceRuleMap = array ();
                $applicableRules = $product->getCatalogRedemptionRules ( $customer );
                
                foreach ( $applicableRules as $i => &$r ) {
                    $r = ( array ) $r;
                    $ruleId = $r [TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID];
                    $rule = Mage::getModel ( 'rewards/catalogrule_rule' )->load ( $ruleId );

                    if ($rule->getId ()) {
                        $productPriceRuleMap [$ruleId] = $rule->getPointSliderSettings ( $store, $product, $customer, $price );
                    }
                }
                
                $content = Mage::helper('core')->jsonEncode($productPriceRuleMap);
                $this->getResponse()->setBody($content);
            } catch ( Exception $e ) {
                $this->getResponse()->setBody("Error: " . $e->getMessage());
            }
	}

    /**
     * Update rule definitions based on product configured options
     * @return \TBT_Rewards_Product_View_RedeemController
     */
    public function updateRuleOptionsOnProductOptionChangeAction()
    {
        $response = array();
        $product = $this->_ensureProduct();
        $redeemBlock = $this->getLayout()->createBlock('rewards/product_view_points_redeemed');
        $response['rule_map'] = $redeemBlock->getApplicableRulesMap();
        $response['product_final_price'] = $product->getFinalPrice();
        $rewardsHelper = Mage::helper('rewards');
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody($rewardsHelper->toJson($response));

        return $this;
    }

    /**
     * Update points-only cost
     * @return \TBT_Rewards_Product_View_RedeemController
     */
    public function updatePointsOnlyOnProductOptionChangeAction()
    {
        $product = $this->_ensureProduct();

        $customer = false;

        if (Mage::getSingleton ( 'rewards/session' )->isCustomerLoggedIn ()) {
            $customer = Mage::getSingleton ( 'rewards/session' )->getCustomer ();
        }

        $pointsCost = $product->getSimplePointsCost($customer);

        $this->getResponse()->setHeader('Content-type', 'text/html');
        $this->getResponse()->setBody($pointsCost);

        return $this;
    }

    /**
     * Initialize Product with configured options
     * @return Mage_Catalog_Model_Product|TBT_Rewards_Model_Catalog_Product
     */
    private function _ensureProduct()
    {
        $requestParams = $this->getRequest()->getParams();
        $buyRequest = $this->_getProductRequest($requestParams);

        $pid = $this->getRequest()->get("product");

        $product = Mage::getModel('rewards/catalog_product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($pid);

        $product->getTypeInstance(false)->prepareForCartAdvanced($buyRequest, $product);

        Mage::register('product', $product);

        return $product;
    }

}

