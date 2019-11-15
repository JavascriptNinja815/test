<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Model_Mysql4_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/queue');
    }
}