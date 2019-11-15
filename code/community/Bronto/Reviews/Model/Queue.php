<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Model_Queue extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/queue');
    }

    public function load($id, $column = null)
    {
        parent::load($id, 'order_id');
        if (!$this->getId()) {
            $this->setId($id);
        }

        return $this;
    }
}
