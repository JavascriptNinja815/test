<?php
class Collinsharper_FraudBlock_Model_Mysql4_Fraud extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("fraudblock/fraud", "fraud_block_id");
    }
}