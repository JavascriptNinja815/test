<?php

$this->startSetup();
 
$installer = $this;

$installer->startSetup();


 
// $entity = $this->getEntityTypeId('order');



$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_order_icon_entity');

$sql=<<<SQLTEXT
drop table if exists {$tableName};
CREATE TABLE if not exists {$tableName} (
  `icon_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
  `name` varchar(255) DEFAULT NULL COMMENT 'Shows what entity history is bind to.',
  `image` varchar(250) DEFAULT NULL COMMENT 'Shows what entity history is bind to.',
  `comment` text COMMENT 'Comment',
  `is_visible_on_front` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Not Currently used',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
  PRIMARY KEY (`icon_id`),
  KEY `IDX_SALES_FLAT_ORDER_STATUS_HISTORY_PARENT_ID` (`icon_id`),
  KEY `IDX_SALES_FLAT_ORDER_STATUS_HISTORY_CREATED_AT` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=utf8 COMMENT='Collins Harper  Flat  icons entity ';



delete from {$tableName};
INSERT INTO `{$tableName}` VALUES (309,'Billing / Shipping Addresses do not match.','BS.png','Billing / Shipping Addresses do not match',0,'2015-03-15 14:48:05'),
(310,'Payment was CC and AVS code did not match list','avs.png','Payment was CC and AVS code did not match list',0,'2015-03-15 14:48:41'),
(311,'Payment was CC and CVV did not match list','CVV.png','Payment was CC and CVV did not match list',0,'2015-03-15 14:49:08'),
(312,'Customer / Email has Invalid Orders','orders.png','Customer / Email has other Orders not (complete|closed|canceled)',0,'2015-03-15 14:49:30'),
(313,'IP Country Error','IP2.png','IP is not listed as US or Canada',0,'2015-03-15 14:50:03'),
(314,'Order Item Count','orders.png','More than 2 items on Order',0,'2015-03-15 14:50:58'),
(315,'High Order Total','OVER.png','Order Total over 1400',0,'2015-03-15 14:51:17'),
(316,'Ship country not US || CA','shipca.png','Ship country not US || CA',0,'2015-03-15 14:51:33'),
(317,'Bill Country is not CA || US','billus.png','Bill Country is not CA || US',0,'2015-03-15 14:51:43');

SQLTEXT;



$installer->run($sql);





$tableName = $resource->getTableName('ch_sales_flat_order_icons');

$sql=<<<SQLTEXT
CREATE TABLE if not exists {$tableName} (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
  `order_id` int(10) unsigned NOT NULL COMMENT 'Order Id',
  `comment` text COMMENT 'Comment',
  `icon_id` int(10) DEFAULT NULL COMMENT 'icon ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
  PRIMARY KEY (`entity_id`),
  KEY `IDX_SALES_FLAT_ORDER_STATUS_HISTORY_PARENT_ID` (`order_id`),
  KEY `IDX_SALES_FLAT_ORDER_STATUS_HISTORY_CREATED_AT` (`created_at`),
  unique (icon_id, order_id),
  CONSTRAINT `FK_CE7C71E74CB74DDACE345` FOREIGN KEY (`order_id`) REFERENCES `sales_flat_order` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=utf8 COMMENT='Collins Harper Sales Flat Order icons';
SQLTEXT;

$installer->run($sql);



/* save the setup */
$this->endSetup();
