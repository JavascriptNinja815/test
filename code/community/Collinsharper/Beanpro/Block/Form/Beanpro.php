<?php

      
class Collinsharper_Beanpro_Block_Form_Beanpro extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanpro/form/beanpro.phtml');
    }
	
	public function hasVerification() 
	{
		return true; 
	}

    public function isTestMode()
    {
        return Mage::helper('beanpro')->getTest() == 1;
    }

}
