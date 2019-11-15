<?php
/**
 * Created by JetBrains PhpStorm.
 * User: shaneray
 * Date: 8/18/14
 * Time: 8:26 AM
 * To change this template use File | Settings | File Templates.
 */ 
class Collinsharper_Chcustomeruploads_Model_Resource_Upload_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('collinsharper_chcustomeruploads/upload');
    }

}