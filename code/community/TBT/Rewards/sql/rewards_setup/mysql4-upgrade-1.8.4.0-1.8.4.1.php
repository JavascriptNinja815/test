<?php

$installer = $this;

$installer->startSetup();

$installer->addColumns($this->getTable('sales_flat_quote'),
    array("
        `rewards_valid_redemptions` VARCHAR(255) NULL DEFAULT NULL AFTER `rewards_base_discount_tax_amount`
    ")
);

// Clear cache.
$installer->prepareForDb();

$installer->endSetup();
