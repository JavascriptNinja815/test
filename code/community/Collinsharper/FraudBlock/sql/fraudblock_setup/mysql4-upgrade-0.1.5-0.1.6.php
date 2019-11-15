<?php
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');

$installer->startSetup();

try {
	$installer->run("ALTER TABLE " . $this->getTable('ch_fraud_block') . " CONVERT TO CHARACTER SET utf8;");
} catch (Exception $e) {

}

try {
	$installer->run("ALTER TABLE " . $this->getTable('ch_fraud_ban') . " CONVERT TO CHARACTER SET utf8;");
} catch (Exception $e) {

}

try {
	$installer->run("ALTER TABLE " . $this->getTable('ch_fraud_tracking') . " CONVERT TO CHARACTER SET utf8;");
} catch (Exception $e) {

}

try {
	$installer->run("ALTER TABLE " . $this->getTable('ch_fraud_block') . " ENGINE = InnoDB");
} catch (Exception $e) {

}

try {
	$installer->run("ALTER TABLE " . $this->getTable('ch_fraud_ban') . " ENGINE = InnoDB");
} catch (Exception $e) {

}

try {
	$installer->run("ALTER TABLE " . $this->getTable('ch_fraud_tracking') . " ENGINE = InnoDB");
} catch (Exception $e) {

}

$installer->endSetup();
