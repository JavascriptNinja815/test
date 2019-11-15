<?php


class Collinsharper_Beanstreamprofiles_Block_Info_Beanstreamprofilesstored extends Mage_Payment_Block_Info_Cc
{
    /**
     * Init default template for block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanstreamprofiles/info/beanstreamprofiles_stored.phtml');
    }

 
}