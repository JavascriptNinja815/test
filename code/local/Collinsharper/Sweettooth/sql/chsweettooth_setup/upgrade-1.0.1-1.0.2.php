<?php 

$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE {$this->getTable('ch_sweettooth_event')}
    ADD COLUMN `event_data` text NOT NULL;
");

$installer->endSetup();
