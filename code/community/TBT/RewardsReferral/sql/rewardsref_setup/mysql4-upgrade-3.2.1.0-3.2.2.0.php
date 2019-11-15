<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('rewardsref_referral'), 'invitation_message', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'after'     => 'referral_status',
        'comment'   => 'Invitation Initial Message'
    ));

$installer->endSetup();
