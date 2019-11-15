<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()
    ->addColumn($installer->getTable('sales/quote'), 'rewards_points_spending', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length'    => 10,
        'nullable'  => true,
        'after'     => 'applied_redemptions',
        'comment'   => 'Points Spending'
    ));
$installer->endSetup();
