<?php

class Collinsharper_FraudBlock_Model_Mysql4_Chfraudtrack extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("fraudblock/chfraudtrack", "fraud_tracking_id");
    }

}