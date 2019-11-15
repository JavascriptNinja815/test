<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("ALTER TABLE  sales_flat_order ADD COLUMN order_source VARCHAR(255);");

$installer->run("ALTER TABLE  sales_flat_order_grid ADD COLUMN order_source VARCHAR(255);");

$installer->run("ALTER TABLE  sales_flat_quote ADD COLUMN order_source VARCHAR(255);");

$installer->endSetup();
