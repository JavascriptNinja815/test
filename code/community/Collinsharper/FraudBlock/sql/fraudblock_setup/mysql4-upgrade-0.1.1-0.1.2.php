<?php
$installer = $this;
$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_fraud_block');

$sql=<<<SQLTEXT
CREATE TABLE if not exists {$tableName} (
fraud_block_id int( 11 ) NOT NULL AUTO_INCREMENT ,
ip varchar( 15 ) ,
failed_attempts_count int( 11 ) ,
last_attempt_at int( 11 ) ,
PRIMARY KEY ( fraud_block_id )
)
SQLTEXT;

$installer->run($sql);


$tableName = $resource->getTableName('ch_fraud_ban');

$sql=<<<SQLTEXT
CREATE TABLE if not exists {$tableName} (
fraud_ban_id int( 11 ) NOT NULL AUTO_INCREMENT ,
customer_id int( 11 ) ,
ip varchar( 15 ) ,
email varchar( 250 ) ,
domain_a varchar( 250 ) ,
domain_b varchar( 250 ) ,
domain_c varchar( 250 ) ,
browser_hash varchar( 250 ) ,
cc_hash_a varchar( 250 ) ,
cc_hash_b varchar( 250 ) ,
cc_hash_c varchar( 250 ) ,
cc_hash_d varchar( 250 ) ,
cc_hash_e varchar( 250 ) ,
banned int( 1 ) ,
comment text ,
created_at timestamp,
updated_at timestamp,
index ( customer_id ),
index ( ip ),
index ( email ),
index ( banned ),
index ( cc_hash_a ),
index ( cc_hash_b ),
index ( cc_hash_b ),
index ( cc_hash_c ),
index ( cc_hash_d ),
index ( browser_hash ),
index ( domain_a ),
index ( domain_b ),
index ( domain_c ),
PRIMARY KEY ( fraud_ban_id )
)
SQLTEXT;

$installer->run($sql);



$installer->endSetup();
	 
