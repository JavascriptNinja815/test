<?php


class Collinsharper_Beanstreamprofiles_Block_Info_Beanstreamprofiles extends Mage_Payment_Block_Info_Cc
{
    /**
     * Init default template for block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanstreamprofiles/info/beanstreamprofiles.phtml');
    }

 
}