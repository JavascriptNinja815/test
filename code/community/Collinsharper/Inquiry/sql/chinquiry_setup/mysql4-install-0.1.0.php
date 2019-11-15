<?php
$installer = $this;
$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$tableName = $resource->getTableName('ch_inquiry');

$sql=<<<SQLTEXT
CREATE TABLE {$tableName} (
entity_id int( 11 ) NOT NULL AUTO_INCREMENT ,
inquiry_type tinyint(1),
store_id int( 1 ) ,
customer_id int( 11 ) ,
ip varchar( 32 ) ,
name varchar( 200 ) ,
email varchar( 64 ) ,
post_data text ,
status tinyint(1) default '1',
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Created At',
key ( email ),
key ( customer_id),
key ( store_id ),
PRIMARY KEY ( entity_id )
)
SQLTEXT;

$installer->run($sql);



$installer->endSetup();
	 
