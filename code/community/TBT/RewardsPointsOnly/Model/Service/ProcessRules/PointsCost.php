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
 * Facade Service part used to predict Points Only Price Cost
 * @package     TBT_RewardsPointsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Model_Service_ProcessRules_PointsCost extends Varien_Object
{
    /**
     * Predict Points Only Price Cost for all Product Types
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param int $websiteId
     * @param int $customerGroupId
     * @param float $prefPrice
     * @return array
     */
    public function getPointsCost(Mage_Catalog_Model_Product $product, $websiteId, $customerGroupId, $prefPrice = null)
    {
        $pointsCost = array();
        $priceIsIncludingTax = Mage::helper('tax')->priceIncludesTax();

        if ($prefPrice === 0 || $prefPrice === 0.0) {
            return array();
        }

        if ($prefPrice) {
            $minimalProductPrice = $maximalProductPrice = $prefPrice;
        } elseif ($product->getTypeId() === 'bundle') {
            list($minimalProductPrice, $maximalProductPrice) = $product->getPriceModel()->getTotalPrices($product, null, $priceIsIncludingTax, true);
        } else {
            list($productPriceExclTax, $productPriceInclTax) = Mage::getModel('rewards/service_catalog_product_price')
                ->getPricesForProduct($product);

            $productPrice = ($priceIsIncludingTax) ? $productPriceInclTax : $productPriceExclTax;

            $minimalProductPrice = $maximalProductPrice = Mage::helper('tax')->getPrice($product, $productPrice, $priceIsIncludingTax);
        }

        if ($minimalProductPrice < 0.00001 && $maximalProductPrice < 0.00001) {
            return $pointsCost;
        }

        foreach ($this->_getRules($websiteId, $customerGroupId) as $rule) {
            if ($rule->getConditions()->validate($product)) {
                switch ($rule->getPointsAction()) {
                    case TBT_RewardsPointsOnly_Model_Rule_Type::REWARDS_POINTONLY_DEDUCT_POINTS:
                        if ($prefPrice) {
                            $numberOfSelectableOptions = 1;
                        } else {
                            $numberOfSelectableOptions = Mage::helper('rewardspointsonly')
                                ->getNumberOfSelectableBundleOptions($product);
                        }

                        if ($product->getTypeId() === 'bundle'
                            && (int) $product->getPriceType() === (int) Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED
                        ) {
                            $numberOfSelectableOptions++;
                        }

                        $pointsMin = $rule->getPointsAmount();
                        $pointsMax = $rule->getPointsAmount() * $numberOfSelectableOptions;
                        $pointsRuleType = "fixed";
                        break;
                    case TBT_RewardsPointsOnly_Model_Rule_Type::REWARDS_POINTSONLY_DEDUCT_BY_AMOUNT_SPENT:
                        $pointsUsesMin = $minimalProductPrice / $rule->getPointsAmountStep() * $rule->getPointsAmount();
                        $pointsMin = $rule->getPointsAmount() * $pointsUsesMin;

                        $pointsUsesMax = $maximalProductPrice / $rule->getPointsAmountStep() * $rule->getPointsAmount();
                        $pointsMax = $rule->getPointsAmount() * $pointsUsesMax;
                        $pointsRuleType = "dynamic";
                        break;
                }

                $pointsMin = round($pointsMin + 0.49, 0, PHP_ROUND_HALF_UP);
                $pointsMax = round($pointsMax + 0.49, 0, PHP_ROUND_HALF_UP);

                $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
                $pointsStrMin = Mage::helper('rewards')->getPointsString(array($currencyId => $pointsMin));
                $pointsStrMax = Mage::helper('rewards')->getPointsString(array($currencyId => $pointsMax));

                $pointsCost = array(
                    'points_min_value' => $pointsMin,
                    'points_min_str'   => $pointsStrMin,
                    'points_max_value' => $pointsMax,
                    'points_max_str'   => $pointsStrMax,
                    'points_rule_type' => $pointsRuleType
                );
                

                $found = true;
                break;
            }
        }

        return $pointsCost;
    }

    /**
     * Predict Item Points Only Price Cost
     * 
     * @param mixed $item
     * @param int|float $qty
     * @return array
     */
    public function getItemPointsCost($item, $qty = null)
    {
        if (
            !$item instanceof Mage_Sales_Model_Quote_Item
            && !$item instanceof Mage_Sales_Model_Order_Item
        ) {
            return array();
        }

        $pointsUnitPrice = 0;
        $pointsRowTotalPrice = 0;

        if ($item->getHasChildren()) {
            if ($item instanceof Mage_Sales_Model_Quote_Item) {
                $childrenItems = $item->getChildren();
            } else {
                $childrenItems = $item->getChildrenItems();
            }

            foreach ($childrenItems as $childItem) {
                $pointsHash = $childItem->getRewardsPointsonlyHash();
                $pointsData = Mage::helper('rewards')->unhashIt($pointsHash);

                if (empty($pointsData)) {
                    continue;
                }

                $qtyChild = ($qty) ? $qty : $pointsData['points_qty'];

                $pointsUnitPrice += (int) ($pointsData['points_amount'] * $pointsData['points_uses'] * -1);
                $pointsRowTotalPrice += (int) ($pointsData['points_amount'] * $pointsData['points_uses'] * -1 * $qtyChild);
            }
        }

        $pointsHash = $item->getRewardsPointsonlyHash();

        $pointsData = Mage::helper('rewards')->unhashIt($pointsHash);

        if (empty($pointsData)) {
            if ($pointsUnitPrice < 1) {
                return array();
            }
        } else {
            $qty = ($qty) ? $qty : $pointsData['points_qty'];

            $pointsUnitPrice += (int) ($pointsData['points_amount'] * $pointsData['points_uses'] * -1);
            $pointsRowTotalPrice += (int) ($pointsData['points_amount'] * $pointsData['points_uses'] * -1 * $qty);
        }

        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        $pointsUnitPriceString = Mage::helper('rewards')->getPointsString(array($currencyId => $pointsUnitPrice));
        $pointsRowTotalPriceString = Mage::helper('rewards')->getPointsString(array($currencyId => $pointsRowTotalPrice));

        return array(
            'points_unit_price' => $pointsUnitPrice,
            'points_unit_price_string' => $pointsUnitPriceString,
            'points_row_total_price' => $pointsRowTotalPrice,
            'points_row_total_price_string' => $pointsRowTotalPriceString
        );
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
}
