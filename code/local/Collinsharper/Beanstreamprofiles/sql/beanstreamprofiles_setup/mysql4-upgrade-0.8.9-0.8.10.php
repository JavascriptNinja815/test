<?php


$installer = $this;
/* @var $installer Mage_Catalog_Model_Entity_Setup */

$table =  Mage::getSingleton('core/resource')->getTablename('collinsharper_beanstreamprofiles');
$installer->deleteConfigData('catalog/category/root_id', 'stores');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
		 
$sql = " 
alter table `{$table}` add index (entity_id);
alter table `{$table}` add index (customer_id);
alter table `{$table}` add index (default_card);
ALTER TABLE  `{$table}` CHANGE  `entity_id`  `entity_id` INT( 11 ) NOT NULL AUTO_INCREMENT;
 
";

$cw->query($sql);


$installer->endSetup();
