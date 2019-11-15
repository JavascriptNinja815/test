<?php
/**
 * Created by JetBrains PhpStorm.
 * User: shaneray
 * Date: 8/18/14
 * Time: 8:24 AM
 * To change this template use File | Settings | File Templates.
 */ 
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();


$sql =
    "CREATE TABLE IF NOT EXISTS `{$installer->getTable('collinsharper_chcustomeruploads/customer_entity_chuploads')}` (
        `entity_id` INT(11) primary key auto_increment,
        `customer_id` INT(11),
        `order_ids` varchar(255),
        `quote_ids` varchar(255),
        `filename` varchar(255) DEFAULT NULL,
        `filepath` varchar(255) DEFAULT NULL,
        `status` tinyint(1) unsigned DEFAULT '1',
        `comment` varchar(255) DEFAULT NULL,
        `created_at` TIMESTAMP NULL,
        `updated_at` TIMESTAMP NULL,
        UNIQUE KEY (`customer_id`, `filename`),
        KEY (`order_ids`),
        KEY (`quote_ids`),
        KEY (`filename`),
        KEY (`filepath`),
        KEY (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// TODO: add in FKC
$installer->run($sql);


$installer->endSetup();