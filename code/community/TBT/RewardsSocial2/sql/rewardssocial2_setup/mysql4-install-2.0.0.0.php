<?php

$installer = $this;
$installer->startSetup();

// Creating table `rewardssocial2_action`
$installer->attemptQuery("
    CREATE TABLE IF NOT EXISTS `{$installer->getTable('rewardssocial2/action')}` (
        `id` int(10) unsigned NOT NULL auto_increment,
        `customer_id` int(10) unsigned NOT NULL,
        `action` varchar(255) NOT NULL,
        `extra` varchar(255),
        `created_at` timestamp NOT NULL,
        `updated_at` timestamp NOT NULL,
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Social Actions';
");

// Adding Foreign Key to `Customer Id`
$this->addForeignKey(
    'FK_REWARDSSOCIAL2_ACTION_CUSTOMER',
    $this->getTable('rewardssocial2/action'),
    'customer_id',
    $this->getTable('customer/entity'),
    'entity_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, 
    Varien_Db_Ddl_Table::ACTION_CASCADE
);

// Adding Indexes
$installer->addIndex(
    $this->getTable('rewardssocial2/action'),
    array('created_at'),
    'IDX_REWARDSSOCIAL2_ACTION_CREATED_AT'
);

$installer->addIndex(
    $this->getTable('rewardssocial2/action'),
    array('customer_id', 'extra'),
    'IDX_REWARDSSOCIAL2_ACTION_CUSTOMER_EXTRA'
);

$installer->endSetup();
