<?php


$installer = $this;
/* @var $installer Mage_Catalog_Model_Entity_Setup */

$table =  Mage::getSingleton('core/resource')->getTablename('collinsharper_beanstreamprofiles');

$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
		 
$sql = " 
 alter table `{$table}` add column trnOrderNumber varchar(50) default 0;
 alter table `{$table}` add column dob varchar(50) default 0;
alter table `{$table}` add index (trnOrderNumber);
 
";

$cw->query($sql);


$installer->endSetup();
