<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kl
 */
$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE `{$installer->getTable('sales/order')}` ADD COLUMN `post_data` LONGTEXT NULL  AFTER `customer_note`;
");
$installer->endSetup();
