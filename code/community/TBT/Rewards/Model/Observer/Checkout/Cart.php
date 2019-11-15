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
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkot Cart Obserever
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Checkout_Cart
{
    /**
     * Check customer can afford points spending
     * @param Varien_Event_Observer $observer
     * @see event `checkout_cart_save_before`
     * @return \TBT_Rewards_Model_Observer_Checkout_Cart
     */
    public function checkCustomerCanAffordPoints(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getCart()->getQuote();

        /**
         * Reset all points applied in case of persistent cart active and customer not logged-in
         */
        if (
            !Mage::app()->getStore()->isAdmin()
            && !Mage::getSingleton('customer/session')->isLoggedIn()
        ) {
            $quote->setPointsSpending(0);
            $appliedRules = Mage::getModel('rewards/salesrule_list_applied')->initQuote($quote);
            $appliedRules->reset()->saveToQuote($quote);

            $quote->setTotalsCollectedFlag(false);
            
            return $this;
        }

        $points = (int) Mage::app()->getRequest()->getParam("points_spending");

        if (!$points) {
            $points = (int) $quote->getPointsSpending();
        }
        
        $customer = Mage::getSingleton('rewards/session')->getSessionCustomer();
        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        if ($points > 0 && !$customer->canAfford($points, $currencyId)) {
            $quote->setPointsSpending(0);
            $quote->setTotalsCollectedFlag(false);
        }

        /**
         * Remove all spending rules (including checkbox rules) if customer balance is 0
         */
        $customerBalance = $customer->getUsablePointsBalance($currencyId);

        if ((int) $customerBalance === 0) {
            $appliedRules = Mage::getModel('rewards/salesrule_list_applied')->initQuote($quote);
            $appliedRules->reset()->saveToQuote($quote);
        }

        return $this;
    }
}
