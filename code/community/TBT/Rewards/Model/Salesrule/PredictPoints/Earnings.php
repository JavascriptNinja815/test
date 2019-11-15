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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Rules Points Predictions for Earnings
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Salesrule_PredictPoints_Earnings
    extends Varien_Object
{
    /**
     * Rewards Shopping Cart Rules
     * 
     * @var array
     */
    protected $_rules = array();

    /**
     * Prepare Earnings Data for Product
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param int $websiteId
     * @param int $customerGroupId
     * @param Mage_Core_Model_Store $store
     * @param array $buyRequest
     * @return array
     */
    public function getEarningsForProduct(Mage_Catalog_Model_Product $product,
        $websiteId, $customerGroupId, Mage_Core_Model_Store $store, $buyRequest = null
    )
    {
        $pointsData = array();

        foreach ($this->_getRules($websiteId, $customerGroupId) as $rule) {
            if (
                $rule->getPointsAction() === TBT_Rewards_Model_Salesrule_Actions::ACTION_GIVE_BY_AMOUNT_SPENT
                && $this->_isRuleConditionValid($rule, $product)
            ) {
                if ($product->getTypeId() === 'bundle') {
                    $productPriceExclTax = $product->getPriceModel()->getTotalPrices($product, 'min', false, true);
                    $productPriceInclTax = $product->getPriceModel()->getTotalPrices($product, 'min', true, true);
                } else {
                    list($productPriceExclTax, $productPriceInclTax) = Mage::getModel('rewards/service_catalog_product_price')
                        ->getPricesForProduct($product, $buyRequest, true);
                }

                $calculateWithTax = Mage::helper('tax')->priceIncludesTax($store);

                $productPrice = ($calculateWithTax) ? $productPriceInclTax : $productPriceExclTax ;

                $pointsAmountByPrice = (int) ($rule->getPointsAmount() * floor($productPrice / $rule->getPointsAmountStep()));

                $pointsAmount = ($rule->getPointsMaxQty() > 0) ? min(array($pointsAmountByPrice, $rule->getPointsMaxQty())) : $pointsAmountByPrice;

                $pointsData[] = array(
                    'rule_id' => $rule->getId(),
                    'points_amount' => $rule->getPointsAmount(),
                    'points_amount_step' => $rule->getPointsAmountStep(),
                    'amount' => $pointsAmount,
                    'max_amount_allowed' => $rule->getPointsMaxQty()
                );
            }
        }

        return $pointsData;
    }

    /**
     * Prepare Rewards Shopping Cart Earning Rules
     * 
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    protected function _getRules($websiteId, $customerGroupId)
    {
        if (empty($this->_rules[$websiteId][$customerGroupId])) {
            $ruleEarningActions = Mage::getModel('rewards/salesrule_actions')->getDistributionActions();

            $rulesCollection = Mage::getModel('salesrule/rule')->getCollection();
            $rulesCollection->setFlag('validation_filter', false)
                ->setValidationFilter($websiteId, $customerGroupId)
                ->addFieldToFilter('points_action', array('in' => $ruleEarningActions))
                ->addFieldToFilter('points_action', array('neq' => ''));
            $rulesCollection->load();

            $this->_rules[$websiteId][$customerGroupId] = $rulesCollection->getItems();
        }

        return $this->_rules[$websiteId][$customerGroupId];
    }

    /**
     * Validate Condition Rules and Allowed Condition Types for Predictions
     * @param TBT_Rewards_Model_Salesrule_Rule $rule
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _isRuleConditionValid($rule, $product)
    {
        $conditions = $rule->getConditions();

        $found = false;

        foreach ($conditions->getConditions() as $condition) {
            if (
                ! $condition instanceof Mage_SalesRule_Model_Rule_Condition_Product
                || ! $condition instanceof Mage_SalesRule_Model_Rule_Condition_Product_Combine
            ) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return false;
        }

        $conditionsValid = $conditions->validate($product);

        return $conditionsValid;
    }
}
