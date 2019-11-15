<?php
/**
 * Created by JetBrains PhpStorm.
 * User: KL
 * To change this template use File | Settings | File Templates.
 */
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE `{$this->getTable('chreels/printqueue')}` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity ID',
  `customer_id` int(11) unsigned DEFAULT NULL,
  `reel_id` varchar(255) NOT NULL,
  `print_filename` varchar(255) DEFAULT NULL,
  `final_reel_filename` varchar(255) DEFAULT NULL,
  `order_id` int(11) unsigned DEFAULT NULL,
  `order_item_id` int(11) unsigned DEFAULT NULL,
  `qty` int(11) unsigned DEFAULT NULL,
  `status` int(2) DEFAULT '0' COMMENT '0 - nothing, 1 regenerate, 2 ...  9 import  10 complete',
  `note` varchar(255) DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Creation Time',
  `printed_at` timestamp NULL DEFAULT NULL COMMENT 'Update Time',
  PRIMARY KEY (`entity_id`),
  KEY `IDX_REELs_ENTITY_CUSTOMER_ID` (`customer_id`),
  KEY `IDX_REELs_ENTITY_REEL_NAME` (`reel_id`),
  KEY `IDX_REELs_ENTITY_REEL_NAMEs` (`order_id`),
  KEY `IDX_REELs_ENTITY_RdEEL_NAMEs` (`order_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 COMMENT='Reel Table'


");

/*
 *   KEY `IDX_REEL_ENTITY_FRAME_ENTITY_ENTITY_REEL_ID` (`reel_id`),
  CONSTRAINT `FK_REEL_ENTT_ID` FOREIGN KEY (`reel_id`) REFERENCES `{$this->getTable('chreels/reels')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
 */
$installer->endSetup();
