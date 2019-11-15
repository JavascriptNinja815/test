<?php
/**
 * Created by JetBrains PhpStorm.
 * User: KL
 */
$installer = new Mage_Customer_Model_Entity_Setup('core_setup');

$installer->startSetup();
$installer->endSetup();