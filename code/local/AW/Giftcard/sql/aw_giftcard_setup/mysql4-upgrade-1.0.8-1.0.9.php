<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()
    ->addColumn($installer->getTable('aw_giftcard/giftcard'),
        'redemption_code_cost',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'precision' => 12,
            'scale' => 4,
            'nullable' => true,
            'comment' => 'Redemption Code Cost'
        )
    );
$installer->endSetup();