<?php
$installer = $this;
$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_fraud_tracking');

$sql=<<<SQLTEXT
CREATE TABLE {$tableName} (
fraud_tracking_id int( 11 ) NOT NULL AUTO_INCREMENT ,
ip varchar( 15 ) ,
customer_id int( 11 ) ,
order_id int( 11 ) ,
quote_id int( 11 ) ,
blocked_reason int( 1 ) default '0' comment 'reason code on why the user was blocked from checkout',
blocked_reason_rule_id int( 11 ) default '0',
browser_hash varchar(255),
created_at timestamp,
updated_at timestamp,
PRIMARY KEY ( fraud_tracking_id ),
index ( ip ),
index ( customer_id ),
index ( order_id )
)
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 
