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
 * Main Points Only Observer Class
 * @package     TBT_RewardsPointsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Model_Observer
{
    /**
     * Process Points Only Rules
     *
     * @see `global` event `sales_quote_collect_totals_before`
     * @param Varien_Event_Observer $observer
     * @return \TBT_RewardsPointsOnly_Model_Observer
     */
    public function processPointsOnlyRules(Varien_Event_Observer $observer)
    {
        $quote = $observer->getQuote();

        if (!$quote || !$quote->getId()) {
            return $this;
        }

        $isProcessedFirstTime = Mage::registry("rewardspointsonly_process_rules_done");

        if (!$isProcessedFirstTime) {
            Mage::getModel('rewardspointsonly/service_processRules')
                ->validateAndApplyPointsOnItems($quote);

            Mage::register("rewardspointsonly_process_rules_done", true);
        }
    }

    /**
     * Read and transport additional spendings
     * 
     * @see event `rewards_points_spent_on_cart_additional`
     * @see event `rewards_tally_points_spent_on_cart_additional`
     * @param Varien_Event_Observer $observer
     * @return \TBT_RewardsPointsOnly_Model_Observer
     */
    public function appendPointsSpendingsForTransfer(Varien_Event_Observer $observer)
    {
        $pointsObj = $observer->getPointsObj();
        $points = $pointsObj->getPoints();
        $items = $observer->getItems();

        $pointsExist = (bool) $pointsObj->getPointsExist();
        $pointsSign = ((bool)$pointsObj->getForcePositive()) ? -1 : 1;

        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        
        foreach ($items as $item) {
            if ($item->getParetItem()) {
                continue;
            }

            $itemPoints = (array) Mage::helper('rewards')->unhashIt($item->getRewardsPointsonlyHash());

            if (count($itemPoints) > 0) {
                $pointsToTransfer = (int) ($itemPoints['points_amount'] * $pointsSign * $itemPoints['points_uses'] * $itemPoints['points_qty']);

                if (isset($points[$currencyId])) {
                    $points[$currencyId] += $pointsToTransfer;
                } else {
                    $points[$currencyId] = $pointsToTransfer;
                }
                
                $pointsExist = true;
            }
        }

        $pointsObj->setPoints($points);
        $pointsObj->setPointsExist($pointsExist);

        return $this;
    }

    /**
     * Append Rewards Points Only Applied Rule Hash from quote item to order item
     *
     * @see event `sales_convert_quote_item_to_order_item`
     * @param Varien_Event_Observer $observer
     * @return \TBT_RewardsPointsOnly_Model_Observer
     */
    public function appendQuoteItemDataToOrderItem(Varien_Event_Observer $observer)
    {
        $quoteItem = $observer->getItem();
        $orderItem = $observer->getOrderItem();

        $orderItem->setRewardsPointsonlyHash($quoteItem->getRewardsPointsonlyHash());

        return $this;
    }

    /**
     * Force Configurable Prices to 0 in case Product Is Rewards Points Only
     *
     * @see event `catalog_product_type_configurable_price`
     * @param Varien_Event_Observer $observer
     * @return \TBT_RewardsPointsOnly_Model_Observer
     */
    public function forceCartItemProductZeroPrice(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($product->getIsRewardsPointsOnly()) {
            $product->setConfigurablePrice(0);
        }

        return $this;
    }
    
    public function prepareBundleSelectionsPointsOnlyConfig(Varien_Event_Observer $observer)
    {
        $responseObject = $observer->getResponseObject();
        $productSelection = $observer->getSelection();

        $product = Mage::registry('current_product');

        if (!$product instanceof Mage_Catalog_Model_Product) {
            return $this;
        }

        $data = array();

        $selectionPrice = $product->getPriceModel()->getSelectionFinalTotalPrice(
            $product, $productSelection, 1, $productSelection->getSelectionQty()
        );

        if ($selectionPrice < 0.00001) {
            $pointsArr = array();
        } else {
            $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
                ->getPointsCost($product, null, null, $selectionPrice);
        }
        

        if (!empty($pointsArr)) {
            $currencyCaption = Mage::helper('rewards/currency')->getDefaultFullCurrencyCaption();

            $data['rewardspointsonly_points_price_value'] = $pointsArr['points_min_value'];
            $data['rewardspointsonly_points_caption'] = $currencyCaption;
        }

        if (!empty($data)) {
            $additionalOptions = (array) $responseObject->getAdditionalOptions();
            $additionalOptions = array_merge($data, $additionalOptions);

            $responseObject->setAdditionalOptions($additionalOptions);
        }

        return $this;
    }
}
