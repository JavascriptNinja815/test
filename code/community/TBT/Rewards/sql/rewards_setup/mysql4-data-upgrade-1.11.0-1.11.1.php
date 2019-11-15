<?php

$installer = $this;

if (
    $installer->getConnection()->tableColumnExists($installer->getTable('catalogrule/rule'), 'points_only_mode')
    && $installer->getConnection()->tableColumnExists($installer->getTable('catalogrule/rule'), 'points_action')
) {
    $pointsCatalogRulesCollection = Mage::getModel('catalogrule/rule')->getCollection()
        ->addFieldToFilter('points_only_mode', 0)
        ->addFieldToFilter('points_action', array('notnull' => true))
        ->load();

    foreach ($pointsCatalogRulesCollection as $pointsRule) {
        $pointsRule->delete();
    }
}
