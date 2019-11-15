<?php

$installer = $this;

$installer->startSetup();

/**
 * Create table 'rewardspointsonly/rule'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('rewardspointsonly/rule'))
    ->addColumn('rule_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Rule Id')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Name')
    ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, 4000, array(
        ), 'Description')
    ->addColumn('from_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
        ), 'From Date')
    ->addColumn('to_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
        ), 'To Date')
    ->addColumn('customer_group_ids', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'Customer Group Ids')
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
        'default'   => '0',
        ), 'Is Active')
    ->addColumn('conditions_serialized', Varien_Db_Ddl_Table::TYPE_TEXT, '2M', array(
        ), 'Conditions Serialized')
    ->addColumn('actions_serialized', Varien_Db_Ddl_Table::TYPE_TEXT, '2M', array(
        ), 'Actions Serialized')
    ->addColumn('sort_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Sort Order')
    ->addColumn('website_ids', Varien_Db_Ddl_Table::TYPE_TEXT, 4000, array(
        ), 'Website Ids')
    ->addIndex($installer->getIdxName('rewardspointsonly/rule', array('is_active', 'sort_order', 'to_date', 'from_date')),
        array('is_active', 'sort_order', 'to_date', 'from_date'))

    ->setComment('Rewards PointsOnly Rule');
$installer->getConnection()->createTable($table);

$installer->endSetup();
