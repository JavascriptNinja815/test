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
 * This class is used as an observer class for add to cart events
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Adminhtml_CatalogRule_AddToCart
{
    /**
     * Gets triggered on the 'adminhtml_sales_order_create_process_data' event to append points information
     * to a quote item upon being added to the cart
     *
     * @param Varien_Event_Observer $observer
     */
    public function appendPointsQuoteAfterAdd(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        
        if (!$event) {
            return $this;
        }
        
        $orderCreateModel = $event->getOrderCreateModel();
        
        $request = $event->getRequest();
        $isUpdateItemsProcess = isset($request['update_items']) && $request['update_items'] == 1 ? true : false;
        
        if ($isUpdateItemsProcess) {
            try {
                Mage::getSingleton('rewards/catalogrule_service_processRules')
                    ->refactorRedemptions($orderCreateModel->getQuote()->getAllItems());
            } catch (Exception $e) {
                Mage::logException($e);
            }
            return;
        }
        
        if ($isUpdateItemsProcess || !isset($request['item'])) {
            return;
        }
        
        $itemProductIds = array_keys($request['item']);
        
        foreach ($orderCreateModel->getQuote()->getAllItems() as $item) {
            if (!in_array($item->getProduct()->getId(), $itemProductIds)) {
                continue;
            }
            
            $product = $item->getProduct();

            if (!$product || !$item) {
                continue;
            }

            if ($item->getParentItem()) {
                $item = $item->getParentItem();
            }

            $buyRequest = $request['item'][$item->getProduct()->getId()];
            $qty = isset($buyRequest['qty']) ? $buyRequest['qty'] : 1;
            $applyRuleId = isset($buyRequest['catalog_redemption_rule']) ? $buyRequest['catalog_redemption_rule'] : null;
            $applyRuleUses = isset($buyRequest['catalog_redemption_uses']) ? $buyRequest['catalog_redemption_rule'] : null;
            
            try {
                Mage::getSingleton('rewards/catalogrule_service_processRules')
                    ->writePointsToQuote(
                        $product, $applyRuleId, $applyRuleUses, $qty, $item
                    );
            } catch (Exception $e) {
                Mage::helper('rewards')->notice($e->getMessage());
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('rewards')->__(
                        "An error occurred trying to apply the points redemption to the product in your cart: %s",
                        $e->getMessage()
                    )
                );
            }
        }
        
        $orderCreateModel->setRecollect(true);
        
        return $this;        
    }
    
    /**
     * Update Earning Distributions on admin `sales_quote_collect_totals_after`
     * @param Varien_Event_Observer $observer
     */
    public function updateDistributionsCollectTotalsAfter(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $quote->updateItemCatalogPoints();
        
        return $this;
    }
    
    /**
     * Refactor Redemptions on order create index action predispatch
     * @see event: controller_action_predispatch_adminhtml_sales_order_create_index
     * @param Varien_Event_Observer $observer
     */
    public function orderCreateIndexPreDispatch(Varien_Event_Observer $observer)
    {
        $quote = $this->_getAggregatedCart()->getQuote();
        
        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
        
        Mage::getSingleton('rewards/catalogrule_service_processRules')
            ->refactorRedemptions($quote->getAllItems());
    }
    
    /**
     * Aggregation Cart Instance
     * @return TBT_Rewards_Model_Sales_Aggregated_Cart
     */
    protected function _getAggregatedCart()
    {
        return Mage::getSingleton('rewards/sales_aggregated_cart');
    }
}
