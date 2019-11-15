<?php

$installer = $this;

$installer->startSetup();

Mage::helper( 'rewards/mysql4_install' )->addColumns( $installer, $this->getTable( 'sales_flat_quote_item' ),
    array(
        "`rewards_pointsonly_hash` TEXT"
    ) );

Mage::helper( 'rewards/mysql4_install' )->addColumns( $installer, $this->getTable( 'sales_flat_order_item' ),
        array(
            "`rewards_pointsonly_hash` TEXT",
        ) );

Mage::helper( 'rewards/mysql4_install' )->addAttribute( 'order_item', 'rewards_pointsonly_hash',
        array(
            'position' => 1,
            'type' => 'text',
            'label' => Mage::helper( 'rewardspointsonly' )->__( "Rewards Points Only Hash" ),
            'global' => 1,
            'visible' => 0,
            'required' => 0,
            'user_defined' => 0,
            'searchable' => 0,
            'filterable' => 0,
            'comparable' => 0,
            'visible_on_front' => 0,
            'visible_in_advanced_search' => 0,
            'unique' => 0,
            'is_configurable' => 0
        ) );

$installer->endSetup();
