<?php

class Collinsharper_Reels_Model_Printqueue extends Mage_Core_Model_Abstract
{


    const NEW_STATUS = 0;
    const PRINTED_STATUS = 8;
    const FAILED_STATUS = 9;

    public function _construct()
    {
        $this->_init('chreels/printqueue');
    }


    // TODO use this status function to shop option dropdowns in grid and edit
    static function getStatusOptions()
    {
        return array(
            self::NEW_STATUS => mage::helper('chreels')->__('New'),
            self::PRINTED_STATUS => mage::helper('chreels')->__('Printed'),
            self::FAILED_STATUS => mage::helper('chreels')->__('Failed'),

        );
    }


}