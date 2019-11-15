<?php


class Collinsharper_Beanpro_Block_Manage extends Mage_Core_Block_Abstract
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
		return $this->getUrl('/beanpro/customer/savecard', array('_secure' => true));
	}
}
