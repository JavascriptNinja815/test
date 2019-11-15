<?php

      
class Collinsharper_Beanstreamprofiles_Block_Form_Beanstreamprofiles extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanstreamprofiles/form/beanstreamprofiles.phtml');
    }
	
	public function hasVerification() 
	{
		return true; 
	}

}