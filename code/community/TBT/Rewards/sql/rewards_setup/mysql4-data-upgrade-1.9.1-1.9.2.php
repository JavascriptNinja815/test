<?php

$helper = Mage::helper('rewards/config');

if (Mage::helper('core')->isModuleEnabled('TBT_RewardsCoreSpending')) {
    $helper->disableModule('TBT_RewardsCoreSpending');
}

if (Mage::helper('core')->isModuleEnabled('TBT_RewardsCoreCustomer')) {
    $helper->disableModule('TBT_RewardsCoreCustomer');
}

Mage::getConfig()->cleanCache();

