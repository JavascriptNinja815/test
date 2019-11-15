<?php

$this->startSetup();

$table =  Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro');

$cw = Mage::getSingleton('core/resource')
    ->getConnection('core_write');


try {
    $sql = "
 alter table `{$table}` add column trnOrderNumber varchar(50) default 0;

";

    $cw->query($sql);

} catch (Exception $e) {
	Mage::logException($e);
}

try {
    $sql = "
 alter table `{$table}` add column dob varchar(50) default 0;

";

    $cw->query($sql);

} catch (Exception $e) {
	Mage::logException($e);
}

try {
    $sql = "
alter table `{$table}` add index (trnOrderNumber);

";

    $cw->query($sql);

} catch (Exception $e) {
	Mage::logException($e);
}


$this->endSetup();
