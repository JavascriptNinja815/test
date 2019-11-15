<?php


$installer = $this;
/* @var $installer Mage_Catalog_Model_Entity_Setup */

$table =  Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro');
$installer->deleteConfigData('catalog/category/root_id', 'stores');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
		 
$sql = " 
 CREATE TABLE if not exists `{$table}` (
  `entity_id` varchar(50) default NULL,
  `store_id` varchar(20) default NULL,
  `customer_id` varchar(50) default NULL,
  `data_key` varchar(255) default NULL,
  `card_expiry_MMYY` varchar(4) default NULL,
  `cardtype` varchar(20) default NULL,
  `cc_last4` varchar(4) default NULL,
  `payment_type` varchar(20) default NULL,
  `payment_id` varchar(20) default NULL,
  `create_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `update_at` timestamp NOT NULL default '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
";

$cw->query($sql);


$table =  Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro_transaction');


$sql = " 
 CREATE TABLE if not exists `{$table}` (
  `entity_id` int(11) default NULL auto_increment,
  `order_id` varchar(20) default NULL,
  `guid` varchar(64) default NULL,
  `trx` varchar(64) default NULL,
  `amount` decimal(10,4) default NULL,
  `create_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `update_at` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`entity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
";

$cw->query($sql);

$installer->endSetup();
