<?php
$installer = $this;
$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_fraud_tracking');


$sql=<<<SQLTEXT
alter table  {$tableName} add column grand_total decimal(12,4) DEFAULT NULL;
alter table  {$tableName} change column order_id order_id varchar(64) DEFAULT NULL;
)
SQLTEXT;
try {
    $installer->run($sql);

} catch (Exception $e) {

}

$installer->endSetup();