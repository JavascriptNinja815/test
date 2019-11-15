<?php

class Collinsharper_Sweettooth_Model_Resource_SweetEvent extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {

         $this->_init('chsweettooth/ch_sweettooth_event', 'event_id');

    }

}
