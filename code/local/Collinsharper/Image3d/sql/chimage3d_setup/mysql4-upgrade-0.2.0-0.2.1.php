<?php

$installer = $this;

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');

$installer->startSetup();

try {


$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'category_menu_url', array(
    'group'         => 'General',
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Main navigation URL',
    'note'         => 'This will override the URL used when rendering the top nav ({{BASE_URL}}checkout/cart/ for base url)',
    'backend'       => '',
    'visible'       => true,
    'required'      => false,
    'visible_on_front' => true,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

} catch (Exception $e) {


mage::logException($e);
}





$installer->endSetup();
