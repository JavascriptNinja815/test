<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Initialize Model
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        $this->_init('bronto_reviews/queue', 'order_id');
    }
}