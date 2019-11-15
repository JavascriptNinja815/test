<?php
$installer = $this;
$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_fraud_tracking');


$sql=<<<SQLTEXT
alter table  {$tableName} add column email varchar(250);
alter table  {$tableName} add column was_failure tinyint(1);
alter table  {$tableName} add column cc_hash varchar( 250 );
)
SQLTEXT;
try {
    $installer->run($sql);

} catch (Exception $e) {

}

$installer->endSetup();