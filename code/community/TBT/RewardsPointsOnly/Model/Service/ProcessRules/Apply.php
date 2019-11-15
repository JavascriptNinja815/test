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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Facade Service part used to apply Points Only
 * @package     TBT_RewardsPointsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Model_Service_ProcessRules_Apply extends Varien_Object
{
    /**
     * Main Service Method used to validate and apply Points Only
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @return \TBT_RewardsPointsOnly_Model_Service_ProcessRules_Apply
     */
    public function applyPoints(Mage_Sales_Model_Quote $quote)
    {
        $websiteId = Mage::app()->getStore($quote->getStoreId())->getWebsiteId();
        $customerGroupId = $quote->getCustomerGroupId();

        Mage::getModel('rewardspointsonly/service_processRules_remove')
            ->removePoints($quote);

        foreach ($quote->getAllVisibleItems() as $item) {
            $processChildItems = true;

            $foundRule = $this->_hasItemRuleConditionsValid($item, $websiteId, $customerGroupId);

            if ($foundRule === false) {
                $this->_resetItemPoints($item);
                continue;
            }

            $isConfigurable = false;
            $isBundle = false;

            if ($item->getProductType() === 'configurable') {
                $item->getProduct()->setInitialPrice($this->_getProductBasePrice($item, $item->getProduct()))
                    ->setPrice(0)->setIsRewardsPointsOnly(true);
                $ruleData = array();

                if (Mage::helper('rewards/version')->isBaseMageVersionAtMost("1.9.0.1")) {
                    $ruleData = $this->_calculatePoints($item, $foundRule, $websiteId, $customerGroupId);
                }
                
                $this->_writePointsData($item, $ruleData);
                $processChildItems = true;
                $isConfigurable = true;
            } elseif ($item->getProductType() === 'bundle') {
                $isBundle = true;
                if ($item->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                    $item->setOriginalPrice($this->_getProductBasePrice($item, $item->getProduct()));
                    $item->getProduct()->setInitialPrice($this->_getProductBasePrice($item, $item->getProduct()))
                        ->setPrice(0)->setIsRewardsPointsOnly(true);
                    $this->_writePointsData($item, array());
                    $processChildItems = true;
                } else {
                    $item->setOriginalPrice($this->_getProductBasePrice($item, $item->getProduct()));
                    $ruleData = $this->_calculatePoints($item, $foundRule, $websiteId, $customerGroupId);
                    $this->_writePointsData($item, $ruleData);
                    $processChildItems = true;
                }
            } else {
                $item->setOriginalPrice($this->_getProductBasePrice($item, $item->getProduct()));
                $ruleData = $this->_calculatePoints($item, $foundRule, $websiteId, $customerGroupId);
                $this->_writePointsData($item, $ruleData);
                $processChildItems = true;
            }
            
            if ($item->getHasChildren() && $processChildItems) {
                foreach ($item->getChildren() as $childItem) {
                    if ($isConfigurable) {
                        $item->setOriginalPrice($this->_getProductBasePrice($childItem, $childItem->getProduct()));
                    } else {
                        $childItem->setOriginalPrice($this->_getProductBasePrice($childItem, $childItem->getProduct()));
                    }
                    
                    $ruleData = $this->_calculatePoints($childItem, $foundRule, $websiteId, $customerGroupId);
                    $this->_writePointsData($childItem, $ruleData);
                }
            }
        }

        return $this;
    }

    /**
     * Points Only Calculator
     * 
     * @param mixed $item
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    protected function _calculatePoints($item, $rule, $websiteId, $customerGroupId)
    {
        $ruleData = array();

        $product = $item->getProduct();
        $productItemPrice = $this->_getProductItemPrice($item);

        $ruleData['rule_id'] = $rule->getId();

        switch ($rule->getPointsAction()) {
            case TBT_RewardsPointsOnly_Model_Rule_Type::REWARDS_POINTONLY_DEDUCT_POINTS:
                if ($productItemPrice < 0.00001) {
                    break;
                }
                
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_AMOUNT] = $rule->getPointsAmount() * -1;
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_AMOUNT_STEP] = 0;
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_USES] = 1;

                $qtyParent = ($item->getParentItem()) ? $item->getParentItem()->getQty() : 1;
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_QTY] = $item->getQty() * $qtyParent;

                break;
            case TBT_RewardsPointsOnly_Model_Rule_Type::REWARDS_POINTSONLY_DEDUCT_BY_AMOUNT_SPENT:
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_AMOUNT] = $rule->getPointsAmount() * (-1);
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_AMOUNT_STEP] = $rule->getPointsAmountStep();
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_USES] = round(
                    ($productItemPrice / $rule->getPointsAmountStep() * $rule->getPointsAmount()) + 0.49,
                    0,
                    PHP_ROUND_HALF_UP
                );

                $qtyParent = ($item->getParentItem()) ? $item->getParentItem()->getQty() : 1;
                $ruleData[TBT_RewardsPointsOnly_Model_Rule::REWARDSPOINTsONLY_RULE_POINTS_QTY] = $item->getQty() * $qtyParent;

                break;
        }

        $product->setIsRewardsPointsOnly(true);
        $product->setInitialPrice($productItemPrice);
        $product->setPrice(0);

        return $ruleData;
    }

    /**
     * Get Product Price from Item
     *
     * @param mixed $item
     * @return float
     */
    protected function _getProductItemPrice($item)
    {
        $productPrice = $this->_getProductBasePrice($item, $item->getProduct());

        if ($productPrice < 0.00001) {
            $productPrice = $item->getProduct()->getInitialPrice();
        }
        
        $store = Mage::app()->getStore($item->getStoreId());
        
        $taxCalculationModel = Mage::getModel('tax/calculation');
        $taxRequest = $taxCalculationModel->getRateRequest(
            $item->getQuote()->getShippingAddress(),
            $item->getQuote()->getBillingAddress(),
            $item->getQuote()->getCustomerTaxClassId(),
            $store
        );

        $productTaxClassId = $item->getProduct()->getTaxClassId();
        $taxRequest->setProductClassId($productTaxClassId);

        $taxPercent = $taxCalculationModel->getRate($taxRequest);

        if (Mage::helper('tax')->priceIncludesTax()) {
            $productPrice = $productPrice;
        } else {
            $productPrice = $productPrice * (1 + $taxPercent / 100);
        }

        return $productPrice;
    }

    /**
     * Get Product Base Price (price, special price, tier price)
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    protected function _getProductBasePrice($item, $product)
    {
        $basePrice = 0;

        if ($item->getParentItemId() && $item->getParentItem()->getProductType() === 'configurable') {
            $parentProduct = $item->getParentItem()->getProduct();

            $basePrice = Mage::getModel('rewards/service_catalog_product_price')
                ->getConfigurableBasePrice($parentProduct, Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.9.1.0"), $item->getQty());
        } elseif ($item->getParentItemId() && $item->getParentItem()->getProductType() === 'bundle') {
            $parentProduct = $item->getParentItem()->getProduct();

            $selectionProduct = Mage::helper('rewardspointsonly')->getSelectionProduct($parentProduct, $product);

            $basePrice = $parentProduct->getPriceModel()->getSelectionFinalTotalPrice($parentProduct, $selectionProduct, 1, $item->getQty());

            if ($basePrice < 0.00001) {
                $basePrice = $product->getInitialPrice();
            }
        } else {
            $basePrice = $product->getPriceModel()->getBasePrice($product, $item->getQty());

            if ($basePrice < 0.00001) {
                $basePrice = $product->getInitialPrice();
            }
        }

        return $basePrice;
    }

    /**
     * Write Points Only Data to Item
     * @param mixed $item
     * @param array $ruleData
     * @return \TBT_RewardsPointsOnly_Model_Service_ProcessRules_Apply
     */
    protected function _writePointsData($item, $ruleData = array())
    {
        $ruleHash = Mage::helper('rewards')->hashIt($ruleData);
        $item->setRewardsPointsonlyHash($ruleHash);

        return $this;
    }

    /**
     * Checks if there are Points Only Rules applied
     * @param mixed $item
     * @return boolean
     */
    protected function _hasRulesApplied($item)
    {
        $ruleData = Mage::helper('rewards')->unhashIt($item->getRewardsPointsonlyHash());

        if (count($ruleData) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get Points Only Rules
     *
     * @note Rules should be loaded with singleton model to preserve performance
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    protected function _getRules($websiteId, $customerGroupId)
    {
        return Mage::getSingleton('rewardspointsonly/service_processRules_rules')
            ->getRules($websiteId, $customerGroupId);
    }

    /**
     * Match a Rewards Points Only Rule for item and all children
     * @param mixed $item
     * @param int $websiteId
     * @param int $customerGroupId
     * @return boolean|TBT_RewardsPointsOnly_Model_Rule
     */
    protected function _hasItemRuleConditionsValid($item, $websiteId, $customerGroupId)
    {
        $rules = $this->_getRules($websiteId, $customerGroupId);

        $parentProduct = $item->getProduct();

        $found = false;

        foreach ($rules as $rule) {
            if ($rule->getConditions()->validate($parentProduct)) {
                $found = $rule;

                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $childItem) {
                        $childProduct = $childItem->getProduct();

                        if (!$rule->getConditions()->validate($childProduct)) {
                            $found = false;
                            break;
                        }
                    }
                }
            }

            if ($found !== false) {
                break;
            }
        }

        return $found;
    }

    /**
     * Reset Item Rewards Points Only Data
     * @param mixed $item
     */
    protected function _resetItemPoints($item)
    {
        $item->getProduct()->setIsRewardsPointsOnly(false);
        $this->_writePointsData($item, array());

        if ($item->getHasChildren()) {
            foreach ($item->getChildren() as $childItem) {
                $childItem->getProduct()->setIsRewardsPointsOnly(false);
                $this->_writePointsData($childItem, array());
            }
        }
    }

    /**
     * Calculate configurable product selection price
     *
     * @param   array $priceInfo
     * @param   decimal $productPrice
     * @return  decimal
     */
    protected function _calcSelectionPrice($priceInfo, $productPrice)
    {
        if($priceInfo['is_percent']) {
            $ratio = $priceInfo['pricing_value']/100;
            $price = $productPrice * $ratio;
        } else {
            $price = $priceInfo['pricing_value'];
        }
        return $price;
    }

    protected function _getValueByIndex($values, $index) {
        foreach ($values as $value) {
            if($value['value_index'] == $index) {
                return $value;
            }
        }
        return false;
    }
}
