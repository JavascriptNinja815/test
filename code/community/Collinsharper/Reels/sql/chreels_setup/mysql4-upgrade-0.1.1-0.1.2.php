<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Rudie Wang
 * To change this template use File | Settings | File Templates.
 */
$installer = $this;

$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('chreels/reels'),
        'platform',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
            'length' => 16,
            'nullable' => true,
            'default' => null,
            'comment' => 'Created Platform. null: Web, Other: Mobile or iOS version'
        )
    );

$installer->endSetup();
