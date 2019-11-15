<?php

$this->startSetup();
 
$installer = $this;

$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_order_icon_entity');

$sql=<<<SQLTEXT
alter table {$tableName} drop column `order_id`;
alter table {$tableName} add image varchar(255) after `name`;

SQLTEXT;

try {
$installer->run($sql);
} catch (Exception $e) {
    mage::logException($e);
}

/* save the setup */
$this->endSetup();
