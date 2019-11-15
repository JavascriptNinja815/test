<?php


class Collinsharper_Beanpro_CustomerController extends Mage_Core_Controller_Front_Action
{

	protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

	public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched())
		{
            return;
        }

		if (!$this->_getSession()->authenticate($this))
		{
			$this->setFlag('', 'no-dispatch', true);
		}
    }

	public function indexAction()
    {
        $this->_redirect('/');
		return;
    }

    public function manageAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function deletecardAction()
    {
		$tokenId = $this->getRequest()->getParam('data_key');
		
		try {
			$return = Mage::helper('beanpro')->deleteToken($tokenId);
		}
		catch (Exception $e) {
			$error_message = $e->getMessage();
		}

		if(!isset($error_message))
		{
			$this->_getSession()->addSuccess(Mage::helper('beanpro')->__('Card deleted Successfully'));
		}
		else
		{
			$this->_getSession()->addError(Mage::helper('beanpro')->__($error_message));
		}
		$this->_redirect('*/*/manage');
   }

    public function defaultcardAction()
    {
		$tokenId = $this->getRequest()->getParam('data_key');
		
		try {
			$return = Mage::helper('beanpro')->setTokenDefault($tokenId);
		}
		catch (Exception $e) {
			Mage::logException($e);
			$error_message = $e->getMessage();
		}

		if(!isset($error_message))
		{
			$this->_getSession()->addSuccess(Mage::helper('beanpro')->__('Card  Successfully Updated'));
		}
		else
		{
			$this->_getSession()->addError(Mage::helper('beanpro')->__($error_message));
		}
		$this->_redirect('*/*/manage');
   }

    public function savecardAction()
    {
		$data = $this->getRequest()->getParams();
		$pay_mod = mage::getModel('beanpro/PaymentMethod');
		$error_message = "General failure";
		
		// wrap in try catch add error
		try {
			$return = $pay_mod->createToken($data);
		}
		catch (Exception $e) {
			$error_message = $e->getMessage();
			mage::log(__CLASS__ . " EXCEPTINON ". print_r($e->getMessage(),1));
		}

		if($return == 1)
		{
			$this->_getSession()->addSuccess(Mage::helper('beanpro')->__('Card added Successfully'));
		}
		else
		{
//			$this->_getSession()->addError(Mage::helper('beanpro')->__((string)($return->Verbiage[0] ? $return->Verbiage[0] : $error_message)));
			$this->_getSession()->addError(Mage::helper('beanpro')->__((string)($return ? $return : $error_message)));
		}
		$this->_redirect('*/*/manage');
   }

}
