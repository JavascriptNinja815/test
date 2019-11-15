<?php
class Collinsharper_Inquiry_Model_Mysql4_Inquiry extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("chinquiry/inquiry", "entity_id");
    }
}