<?php

$this->startSetup();

$this->modifyColumn($this->getTable('tbtmilestone/rule'),'rewards_special_id', "INT(11) NULL DEFAULT NULL COMMENT  'For backwards compatibility with rewards_special rules'");
$this->addForeignKey('FK_REWARDS_SPECIAL_ID', $this->getTable('tbtmilestone/rule'), "rewards_special_id", $this->getTable('rewards/special'), "rewards_special_id", "CASCADE", "CASCADE");

$this->endSetup();
