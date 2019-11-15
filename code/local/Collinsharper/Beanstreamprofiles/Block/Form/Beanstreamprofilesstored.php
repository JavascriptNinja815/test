<?php

      
class Collinsharper_Beanstreamprofiles_Block_Form_Beanstreamprofilesstored extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('beanstreamprofiles/form/beanstreamprofiles_stored.phtml');
    }
	
	public function hasVerification() 
	{
		return (bool)Mage::getStoreConfig('payment/beanstreamprofiles/useccv'); 
	}
	
	public function getStoredCards() 
	{
		mage::log(__CLASS__ . __LINE__ );
		$cust_id = Mage::helper('beanstreamprofiles')->_getQuoteCustomerId();
		$cards = Mage::helper('beanstreamprofiles')->loadStoredAccountId($cust_id);
		return $cards; 
	}

}