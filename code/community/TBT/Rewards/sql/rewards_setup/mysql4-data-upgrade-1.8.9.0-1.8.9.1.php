<?php

$approveOrderPointsOnConfig = TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn::APPROVE_ORDER_POINTS_ON_DO_NOT;

if ((bool) Mage::getStoreConfig('rewards/orders/shouldApprovePointsOnInvoice')) {
    $approveOrderPointsOnConfig = TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn::APPROVE_ORDER_POINTS_ON_INVOICE;
}

if ((bool) Mage::getStoreConfig('rewards/orders/shouldApprovePointsOnShipment')) {
    $approveOrderPointsOnConfig = TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn::APPROVE_ORDER_POINTS_ON_SHIPMENT;
}


Mage::getConfig()->saveConfig('rewards/orders/shouldApprovePointsOn', $approveOrderPointsOnConfig);

// clean config cache
Mage::getConfig()->cleanCache();
