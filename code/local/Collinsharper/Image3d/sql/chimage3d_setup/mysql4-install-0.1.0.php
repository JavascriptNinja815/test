<?php

$installer = $this;
$installer->startSetup();

$sql = 
    "CREATE TABLE IF NOT EXISTS `{$installer->getTable('chimage3d/deposit')}` (
        `deposit_id` INT(11) primary key auto_increment,
        `order_id` INT(11),
        `payment_id` INT(11) DEFAULT NULL UNIQUE,
        `ordered_at` TIMESTAMP NULL,
        `shipped_at` TIMESTAMP NULL,
        `amount_received` DECIMAL(12,4),
        `payment_method` VARCHAR(64),
        UNIQUE KEY (`order_id`, `payment_method`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$installer->run($sql);

$installer->endSetup();
