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
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Observer Sales Order Place Before
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Sales_Order_Place_Before
{
    /**
     * Handle Guest Checkout
     * 
     * @param Varien_Event_Observer $e
     * @event sales_order_place_before
     */
    public function handleGuestCheckout($e)
    {
        $order = $e->getEvent()->getOrder();

        if (Mage::getStoreConfig('rewards/checkout/associate_guest_checkouts_with_account') && $order->getCustomerIsGuest()) {
            $email = $order->getCustomerEmail();
            $website = Mage::app()->getWebsite()->getId();

            // Check if an account exists for this email
            $customer = Mage::getModel("customer/customer"); 
            $customer->setWebsiteId($website); 
            $customer->loadByEmail($email);

            // No customer was found for this email address so we create a new customer
            if (!$customer->getId()) {
                $customer = Mage::getModel("customer/customer")
                    ->setWebsite($website)
                    ->setEmail($email)
                    ->setStoreId($order->getStoreId())
                    ->setFirstname($order->getCustomerFirstname())
                    ->setLastname($order->getCustomerLastname())
                    ->setEmail($email)
                    ->setPassword($this->generatePassword())
                    ->save();
                
                $customer->sendNewAccountEmail();
                Mage::getSingleton('rewards/session')->setGuestCustomerId($customer->getId());
            } else {
                // Update customer information if user already exists (we leave the default guest data otherwise)
                $order->setCustomerIsGuest(false)
                    ->setCustomerGroupId($customer->getGroupId())
                    ->setCustomerFirstname($customer->getFirstname())
                    ->setCustomerLastname($customer->getLastname())
                    ->setCustomerMiddlename($customer->getMiddlename())
                    ->setCustomerPrefix($customer->getPrefix())
                    ->setCustomerSuffix($customer->getSuffix())
                    ->setCustomerTaxvat($customer->getTaxvat())
                    ->setCustomerGender($customer->getGender());
            }

            // set customer
            $order->setCustomer($customer);
            $order->setCustomerId($customer->getId());
            
            Mage::getSingleton('rewards/session')->setCustomer($customer);
        }
    }
    
    /**
     * 
     * @return stringGenerate a random password
     * @return string
     */
    protected function generatePassword()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, 8);
    }
    
    /**
     * Klarna does not call collectTotals when the order is created, so the spending rules are
     * not validated and applied.
     * 
     * @param Varien_Event_Observer $observer
     * @event klarna_place_order_before_merchant_checkbox
     */
    public function fixKlarnaSpendingRules(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        $quote->setTotalsCollectedFlag(false)->collectTotals();
        
        return $this;
    }
}

