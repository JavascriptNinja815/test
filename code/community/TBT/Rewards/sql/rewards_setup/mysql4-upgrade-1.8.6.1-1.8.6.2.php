<?php

// Enable Guest Checkout Account Associations if Idev_OneStepCheckout is enabled
if (Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
    Mage::getConfig()->saveConfig('rewards/checkout/associate_guest_checkouts_with_account', 1);
    Mage::getConfig()->cleanCache();
}
