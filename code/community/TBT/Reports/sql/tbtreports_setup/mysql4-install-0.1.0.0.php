<?php
/**
 * @var $this TBT_Rewards_Model_Mysql4_Setup
 */
$this->startSetup();

$this->attemptQuery("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('tbtreports/indexer_order')}` (
        `order_id` int(10) unsigned NOT NULL COMMENT 'Entity Id in Order Table',
        -- `state` varchar(32) DEFAULT NULL COMMENT 'State in Order Table',
        -- `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At in Order Table',
        `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'Customer Id in Order Table',
        `by_loyalty_customer` boolean not null default 0 COMMENT 'If order was placed by a loyalty customer',
        `by_referred_customer` boolean not null default 0 COMMENT 'If order was placed by a referred customer',
        PRIMARY KEY (`order_id`),
        -- KEY `IDX_TBTREPORTS_ORDER_STATE` (`state`),
        -- KEY `IDX_TBTREPORTS_ORDER_CREATED_AT` (`created_at`),
        KEY `IDX_TBTREPORTS_ORDER_BY_LOYALTY_CUSTOMER` (`by_loyalty_customer`),
        KEY `IDX_TBTREPORTS_ORDER_BY_REFERRED_CUSTOMER` (`by_referred_customer`),
        CONSTRAINT `FK_TBTREPORTS_ORDER_ID_SALES_FLAT_ORDER_ENTITY_ID`
          FOREIGN KEY (`order_id`) REFERENCES `{$this->getTable('sales/order')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_TBTREPORTS_ORDER_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID`
          FOREIGN KEY (`customer_id`) REFERENCES `{$this->getTable('customer_entity')}` (`entity_id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$this->addIndex($this->getTable('tbtmilestone/rule_log'), 'customer_id', 'IDX_CUSTOMER_ID');

// clear cache
$this->prepareForDb();
$this->endSetup();

