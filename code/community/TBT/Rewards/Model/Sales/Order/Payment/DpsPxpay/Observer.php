<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 *
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
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Order Payment Capture Observer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Sales_Order_Payment_DpsPxpay_Observer
{

    /**
     * Observes the 'controller_action_predispatch_checkout_onepage_failure' event & adds compatibility with Dps
     * PaymentExpress by MageBase for automatically canceling order points when payment fails.
     *
     * @param Varien_Event_Observer $observer
     * @return self
     */
    public function cancel(Varien_Event_Observer $observer)
    {
        $lastQuoteId = $this->_getCheckoutSession()->getLastQuoteId();
        $lastOrderId = $this->_getCheckoutSession()->getLastOrderId();

        if (!$lastQuoteId || !$lastOrderId) {
            return $this;
        }
        $order = Mage::getModel('sales/order')->load($lastOrderId);

        $event    = new Varien_Object(array('payment' => $order->getPayment()));
        $observer = new Varien_Event_Observer(array('event' => $event));

        Mage::getSingleton('rewards/sales_order_payment_observer')->automaticCancel($observer);

        return $this;
    }

    /**
     * Retrieves the checkout session.
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
