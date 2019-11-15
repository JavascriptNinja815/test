<?php


$installer = $this;
/* @var $installer Mage_Catalog_Model_Entity_Setup */

$table =  Mage::getSingleton('core/resource')->getTablename('collinsharper_beanstreamprofiles');
$installer->deleteConfigData('catalog/category/root_id', 'stores');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
		 
$sql = "show create table {$table}";
$fetchrow = $cw->fetchRow($sql);
// if(!stristr($fetchrow['Create Table'],'default_card'))
// {
// $sql = "
 // alter table `{$table}` add column default_card tinyint(1) default 0;
// ";

// $cw->query($sql);
// }

$cw->query($sql);


$installer->endSetup();
