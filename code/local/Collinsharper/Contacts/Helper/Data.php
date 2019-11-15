<?php

class Collinsharper_Contacts_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getAllowedMimeTypes()
    {
        return explode(',', 'image/jpeg,image/jpg,image/gif,image/png,application/pdf,application/x-pdf');
    }

    public function debugLog($_message, $_level = null, $filename = "")
    {
        if (strlen($filename) == 0) {
            Mage::log($_message);
        } else {
            Mage::log($_message, $_level, $filename);
        }
    }
}
