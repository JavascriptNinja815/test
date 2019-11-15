<?php
/**
 * Created by JetBrains PhpStorm.
 * User: shaneray
 * Date: 8/25/14
 * Time: 3:05 PM
 * To change this template use File | Settings | File Templates.
 */ 
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');

$installer->startSetup();

try {


$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'category_form', array(
    'group'         => 'General',
    'input'         => 'select',
    'type'          => 'int',
    'label'         => 'Is Category Form?',
    'note'         => 'This will enable a category tab to select specific information for frontend user forms.',
    'backend'       => '',
    'source'       => 'eav/entity_attribute_source_boolean',
    'visible'       => true,
    'required'      => false,
    'visible_on_front' => false,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

} catch (Exception $e) {}


try {

    $installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'category_form_control', array(
    'group'         => 'General',
    'input'         => 'select',
    'type'          => 'int',
    'label'         => 'Category Form Control',
    'note'         => 'This will enable a category form control',
    'backend'       => '',
    'source'       => 'Collinsharper_Chcategoryforms_Model_Source_Categorylist',
    'visible'       => true,
    'required'      => false,
    'visible_on_front' => false,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

} catch (Exception $e) {}

try {
    $installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'category_form_data', array(
        'group'         => 'General',
    //    'input'         => 'select',
        'type'          => 'text',
        'label'         => 'Category Form Data',
        //'note'         => 'this is the data that builds the t ab.',
        'backend'       => '',
   //     'source'       => 'eav/entity_attribute_source_boolean',
        'visible'       => false,
        'required'      => false,
        'visible_on_front' => false,
        'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    ));
} catch (Exception $e) {}



try {
    $installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'category_form_bundle_options', array(
        'group'         => 'General',
        'input'         => 'select',
        'type'          => 'text',
        'label'         => 'Category Form Data',
        //'note'         => 'this is the data that builds the t ab.',
        'backend'       => '',
        'source'       => 'eav/entity_attribute_source_boolean',
        'visible'       => false,
        'required'      => false,
        'visible_on_front' => false,
        'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    ));
} catch (Exception $e) {}




$installer->endSetup();
