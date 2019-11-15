<?php


class Collinsharper_Beanstreamprofiles_Block_Manage extends Mage_Core_Block_Abstract
{

    protected function _construct()
    {
        parent::_construct();
//        $this->setTemplate('internetsecurestored/customer/manage.phtml');
    }

	public function getCards()
	{
		return array();
	}
	
	public function getAddCardAction()
	{
		return $this->getUrl('/beanstreamprofiles/customer/savecard', array('_secure' => true));
	}
}