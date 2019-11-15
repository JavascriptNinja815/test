<?php
/**
 * Created by JetBrains PhpStorm.
 * User: KL
 * To change this template use File | Settings | File Templates.
 */
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE `{$this->getTable('chreels/reels')}` (
 `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity ID',
  `imported_id` int(10) DEFAULT '0',
  `customer_id` int(11) unsigned DEFAULT NULL,
  `anonymous_email` varchar(255) DEFAULT NULL,
  `reel_name` varchar(255) NOT NULL,
  `reel_data` varchar(3000) NOT NULL,
  `status` int(2) DEFAULT '0' COMMENT '0 - nothing, 1 regenerate, 2 ...  9 import  10 complete',
  `is_public` tinyint(1) DEFAULT '0',
  `public_reel_path` varchar(250) DEFAULT NULL,
  `final_reel_file` varchar(250) DEFAULT NULL,
  `thumb` varchar(250) DEFAULT NULL,
  `file_status` int(2) DEFAULT '0' COMMENT '0 - nothing, 1 regenerate, 2 ...  9 import  10 complete',
  `is_ordered` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Creation Time',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Update Time',
  `viewed_at` timestamp NULL DEFAULT NULL COMMENT 'View Time - only recorded for anon users',
  PRIMARY KEY (`entity_id`),
  KEY `IDX_REEL_ENTITY_CUSTOMER_ID` (`customer_id`),
  KEY `IDX_REEL_ENTITY_REEL_NAME` (`reel_name`),
  KEY `status` (`status`),
  KEY `file_status` (`file_status`),
  KEY `imported_id` (`imported_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Reel Table'

");

$installer->endSetup();
