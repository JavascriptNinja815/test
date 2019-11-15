<?php

$this->startSetup();

$table = Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro');
$connection = $this->getConnection();

// For some reason, the new default_card column was commented out... So let's try adding it again.
try {
	// Don't worry, this automatically checks if the column already exists
	$connection->addColumn($table, 'default_card', 'TINYINT(1) NOT NULL DEFAULT 0');
} catch (Exception $e) {
	Mage::logException($e);
}

// The missing default_card column caused this column change to fail, so let's try that again too.
try {
	$connection->changeColumn($table, 'entity_id', 'entity_id', 'INT(11) NOT NULL AUTO_INCREMENT', true);
} catch (Exception $e) {
	Mage::logException($e);
}

// And just to make sure we're up to snuff, let's also ensure these indexes exist just in case.
try {
	$connection->dropIndex($table, 'entity_id');
	$connection->addIndex($table, "IX__{$table}__entity_id", 'entity_id');
} catch (Exception $e) {
	Mage::logException($e);
}

try {
	$connection->dropIndex($table, 'customer_id');
	$connection->addIndex($table, "IX__{$table}__customer_id", 'customer_id');
} catch (Exception $e) {
	Mage::logException($e);
}

try {
	$connection->dropIndex($table, 'default_card');
	$connection->addIndex($table, "IX__{$table}__default_card", 'default_card');
} catch (Exception $e) {
	Mage::logException($e);
}

$this->endSetup();
