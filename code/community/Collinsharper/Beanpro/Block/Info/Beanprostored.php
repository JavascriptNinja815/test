<?php


class Collinsharper_Beanpro_Block_Info_Beanprostored extends Mage_Payment_Block_Info_Cc
{
    /**
     * Init default template for block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanpro/info/beanpro_stored.phtml');
    }

 
}
