<?php


$this->startSetup();

// drop foreign key
$this->dropForeignKey($this->getTable('tbtmilestone/rule_log'), "FK_RULE_ID");
// drop index key
$this->getConnection()->dropKey($this->getTable('tbtmilestone/rule_log'), "FK_RULE_ID");

// modify column to allow null
$this->modifyColumn(
    $this->getTable('tbtmilestone/rule_log'),
    "rule_id",
    "INT UNSIGNED NULL DEFAULT NULL"
);

// re-create foreign key
$this->addForeignKey(
    'FK_RULE_ID',
    $this->getTable('tbtmilestone/rule_log'),
    "rule_id",
    $this->getTable('tbtmilestone/rule'),
    "rule_id",
    "SET NULL"
);


$this->endSetup();