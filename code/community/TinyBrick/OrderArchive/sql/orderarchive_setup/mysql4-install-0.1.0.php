<?php
$installer = $this;

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();


$installer->run("

		-- DROP TABLE IF EXISTS {$this->getTable('orderarchive')};
		CREATE TABLE IF NOT EXISTS {$this->getTable('orderarchive')} (
		`orderarchive_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`order_id` int(10) unsigned NOT NULL,
		`order_group_id` smallint(5) unsigned DEFAULT NULL,
		PRIMARY KEY (`orderarchive_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();