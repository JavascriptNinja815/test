<?php
class Collinsharper_FraudBlock_Model_Mysql4_Fraudban extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("fraudblock/fraudban", "fraud_ban_id");
    }
}