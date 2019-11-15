<?php

class Collinsharper_Ordericons_Block_Adminhtml_Chicons_Helper_Image extends Varien_Data_Form_Element_Image
{
    protected function _getUrl()
    {
        $url = false;
        $x = new Collinsharper_Ordericons_Helper_Data;
        if ($this->getValue()) {
            $url = $x->getMediaUrl() .  $this->getValue();
        }
        return $url;
    }
}

