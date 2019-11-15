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
 * Responsible with adding support for Magento's recurring profiles.
 * NOTE: tested only with Authorize.net CIM - Recurring Profile.
 * Magento, by default, works only with Paypal Express, but we couldn't get that to works (seemed buggy).
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Sales_Recurring_Profile_Observer extends Varien_Object
{

    protected $_quote = null;

    /**
     * This observes 'model_load_after' event and checks if this is a Mage_Sales_Model_Recurring_Profile model load.
     * We are doing 2 things here:
     *  - recording that this is a recurring order
     *  - saving the original order item, because there won't be any quote items here, so using the original order item
     *  points_earned_hash instead to award catalog points in
     * TBT_Rewards_Model_Observer_Sales_Order_Save_Before::supportEarningsForNominalOrders()
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onLoadRecurringProfile(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        if (!$event) {
            return $this;
        }
        $profile = $event->getObject();
        if (!$profile instanceof Mage_Sales_Model_Recurring_Profile) {
            return $this;
        }

        // this will allow us to create the transfer for recurring order, as normally we fail-fast if customer not
        // logged in or not admin mode
        Mage::getSingleton('rewards/session')->setRecurringOrderBeingPlaced(true);
        $this->setProfile($profile);

        // reset these so multiple recurring orders can be processed and award points if it's the case
        Mage::unregister('rewards_process_support_for_recurring_orders');
        Mage::unregister('rewards_createPointsTransfers_run');

        return $this;
    }

    /**
     * Adds support for earning of catalog & cart points for nominal orders (recurring products).
     * We need this because Magento will not use converter to convert from the quote to order, so a bunch of our logic
     * is never run in this case.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function supportRecurringOrders(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        if (!$event) {
            return $this;
        }

        $order = $event->getOrder();
        // if this is not an nominal order (recurring), we are done here
        if (!$order || !is_null($order->getConvertingFromQuote())) {
            return $this;
        }

        if (!$order->getIncrementId() || Mage::registry('rewards_process_support_for_recurring_orders')) {
            return $this;
        }

        // if we have a profile this is a recurring order (not first time customer orders)
        if ($this->getProfile()) {
            $orderedItem = $this->getProfile()->getOrderItemInfo();
            if (isset($orderedItem['quote_id'])) {
                $quote = Mage::getModel('sales/quote')->setStoreId($orderedItem['store_id'])
                    ->load($orderedItem['quote_id']);
                $this->_quote = clone $quote;

                $product = Mage::getModel('catalog/product')->setStoreId($orderedItem['store_id'])
                               ->load($orderedItem['product_id']);

                $this->_quote->addProduct($product);
            }

            $this->_handleRecurringOrder($order);
        } else {
            $this->_handleCreateInitialRecurringOrder($order);
        }

        // mark this as processed to not run it more than once
        Mage::register('rewards_process_support_for_recurring_orders', true);

        return $this;
    }

    /**
     * This handles recurring orders, after the original recurring order & profile was created by customer.
     * self::onLoadRecurringProfile() will create a clone of the original quote and we use that to make sure we rewards
     * customer based on actual catalog & cart rules that applies to the product, respectively the quote.
     *
     * @param  Mage_Sales_Model_Order $order    The new recurring order that is being processed.
     * @return $this
     */
    protected function _handleRecurringOrder(Mage_Sales_Model_Order $order)
    {
        $quoteItems = $this->_quote->getAllItems();

        // fail-safe
        if (!isset($quoteItems[0])) {
            return $this;
        }

        $this->_quote->collectQuoteToOrderTransfers(false);

        $quoteItem = $quoteItems[0];
        // at this points the quote item has freshly validated applied_rule_ids - use those
        $appliedRuleIds = $quoteItem->getAppliedRuleIds();
        $quoteRuleIds   = explode(',', $appliedRuleIds);
        $ruleActions    = Mage::getSingleton('rewards/salesrule_actions');
        $validRuleIds   = array();

        foreach ($quoteRuleIds as $quoteRuleId) {
            $rule = Mage::helper('rewards/rule')->getSalesRule($quoteRuleId);
            // if it's not a Sweet Tooth rule, we don't care
            if (!Mage::helper('rewards/rule')->isPointsRule($rule)) {
                continue;
            }
            // if it's a Sweet Tooth redemption rule, we don't care, as spending of points not allowed on
            // recurring orders
            if ($ruleActions->isRedemptionAction($rule->getPointsAction())) {
                continue;
            }

            array_push($validRuleIds, $quoteRuleId);

        }
        // merge valid rule ids from the quote with the order ones
        $orderRuleIds = explode(',', $order->getAppliedRuleIds());
        foreach ($orderRuleIds as $orderRuleId) {
            if (!empty($orderRuleId) && !in_array($orderRuleId, $validRuleIds)) {
                array_push($validRuleIds, $orderRuleId);
            }
        }
        $validRuleIds = implode(',', array_unique($validRuleIds));
        $order->setAppliedRuleIds($validRuleIds);

        foreach ($order->getAllItems() as $item) {
            $item->setAppliedRuleIds($validRuleIds);

            // reset Sweet Tooth spending discount....no discount for recurring orders
            $discountAmount = $quoteItem->getDiscountAmount();
            $item->setDiscountAmount($discountAmount);

            $baseDiscountAmount = $quoteItem->getBaseDiscountAmount();
            $item->setBaseDiscountAmount($baseDiscountAmount);
        }

        return $this;
    }

    /**
     * This hooks into the process of creating a recurring profile & order when customer orders a recurring product.
     * Because Magento doesn't support promotional rules for nominal orders, we have to go and get the applied_rule_ids
     * from the quote and save them on the order, so we can award points later on in the process + some other things
     * that we need to do because a bunch of default Magento's checkout flow is skipped.
     *
     * @param  Mage_Sales_Model_Order $order
     * @return $this
     */
    protected function _handleCreateInitialRecurringOrder(Mage_Sales_Model_Order $order)
    {
        $appliedRuleIds = null;
        $quoteId        = null;
        foreach ($order->getAddressesCollection() as $address) {
            $ruleIds        = $address->getAppliedRuleIds();
            $appliedRuleIds = empty($ruleIds) ? $appliedRuleIds : $ruleIds;

            $initialQuoteId = $address->getQuoteId();
            $quoteId        = empty($initialQuoteId) ? $quoteId : $initialQuoteId;
        }

        if (is_null($quoteId) || is_null($appliedRuleIds)) {
            // something's wrong if we get here
            return $this;
        }

        $order->setAppliedRuleIds($appliedRuleIds);
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->collectQuoteToOrderTransfers(false);

        // registering on checkout needs this as ParadoxLabs_AuthorizeNetCim creates a customer early on
        if ($quote->getCheckoutMethod(true) == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER) {
            $order->prepareCartPointsTransfers();
        }

        return $this;
    }
}
