<?php

$installer = $this;

$installer->startSetup();

Mage::helper( 'rewards/mysql4_install' )->addColumns( $installer, $installer->getTable('rewardspointsonly/rule'),
    array(
        "`points_action` VARCHAR(25)",
        "`points_amount` INT(11)",
        "`points_amount_step` FLOAT(9,2) DEFAULT '1'",
    ) );

$installer->endSetup();
