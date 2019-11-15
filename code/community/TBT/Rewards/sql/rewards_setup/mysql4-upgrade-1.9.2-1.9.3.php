<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$connection->addColumn($installer->getTable('sales/quote'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/quote_item'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/order'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/order_item'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/invoice'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/invoice_item'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/creditmemo'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));
$connection->addColumn($installer->getTable('sales/creditmemo_item'), 'rewards_cart_discount_map', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'comment'   => 'Rewards Cart Discount Map'
));

$installer->endSetup();
