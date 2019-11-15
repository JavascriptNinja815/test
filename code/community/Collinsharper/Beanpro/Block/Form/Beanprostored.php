<?php

      
class Collinsharper_Beanpro_Block_Form_Beanprostored extends Collinsharper_Beanpro_Block_Form_Beanpro
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanpro/form/beanpro_stored.phtml');
    }
	
	public function hasVerification() 
	{
		return (bool)Mage::getStoreConfig('payment/beanpro/useccv'); 
	}
	
	public function getStoredCards() 
	{
		mage::log(__CLASS__ . __LINE__ );
		$cust_id = Mage::helper('beanpro')->_getQuoteCustomerId();
		$cards = Mage::helper('beanpro')->loadStoredAccountId($cust_id);
		return $cards; 
	}

}
