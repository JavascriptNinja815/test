<?php
/**
 * Created by JetBrains PhpStorm.
 * User: shaneray
 * Date: 8/25/14
 * Time: 3:06 PM
 * To change this template use File | Settings | File Templates.
 */ 
class Collinsharper_Chcategoryforms_Model_Resource_Form extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('collinsharper_chcategoryforms/catalog_category_entity_form', 'entity_id');
    }

}