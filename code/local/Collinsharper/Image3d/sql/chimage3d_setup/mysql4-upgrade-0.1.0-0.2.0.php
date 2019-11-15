<?php

$installer = $this;
$installer->startSetup();

$sql = 
    "
    ALTER table `{$installer->getTable('sales/order')}` add column reels_printed tinyint(1) unsigned not null default 0;
    ALTER table `{$installer->getTable('sales/order')}` add index(reels_printed);
    update `{$installer->getTable('sales/order')}` set reels_printed = 1;

    ";

$installer->run($sql);

$installer->endSetup();
