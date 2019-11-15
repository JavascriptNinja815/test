<?php


class Collinsharper_Beanstreamprofiles_CustomerController extends Mage_Core_Controller_Front_Action
{

	protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

	public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $isAjax = $this->getRequest()->getParam('is_ajax', false);
        $isMarketForm = $this->getRequest()->getParam('deposit_payment_method', false);

        // we allow guests to create cards..
        if($isAjax && $isMarketForm) {
            return;
        }

		if (!$this->_getSession()->authenticate($this)) {
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


    public function createAddressAction()
    {
        $data = $this->getRequest()->getParams();
        $isAjax = $this->getRequest()->getParam('is_ajax', false);
        $isBilling = $this->getRequest()->getParam('is_billing', false);
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customerId);

        mage::log(__METHOD__ . __LINE__ ." wee have CID  " . $customer->getId());

        $errors = array();
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel('customer/address');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_register_address')
            ->setEntity($address);

        $addressData = $addressForm->extractData($this->getRequest(), 'shipping', false);

        mage::log(__METHOD__ . __LINE__ . " adrress data " . print_r($addressData,1));

        $addressErrors = $addressForm->validateData($addressData);
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }

        $address->setId(null)
            ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
            ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
        $addressForm->compactData($addressData);
        $customer->addAddress($address);

        if(!$address->getId()) {
             $address->setCustomerId($customer->getId());
             $address->save();
        }

        mage::log(__METHOD__ . __LINE__ . " wehave address COMPany  " . $address->getCompany());
        mage::log(__METHOD__ . __LINE__ . " wehave address IDd " . $address->getId());

        $addressErrors = $address->validate();

        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }

        if($isAjax) {
            if($isBilling) {
                $block = Mage::app()->getLayout()->createBlock("checkout/onepage_billing")->setName('checkout.onepage.billing.form.custom')->setTemplate('chcategoryform/bill_address.phtml')->toHtml();
            } else {
                $block = Mage::app()->getLayout()->createBlock("checkout/onepage_shipping")->setName('checkout.onepage.shipping.form.custom')->setTemplate('chcategoryform/address.phtml')->toHtml();

            }

            $paymentBlock = Mage::app()->getLayout()
                ->createBlock('page/html')
                ->setBlockId("bs_cards")
                ->setTemplate('beanstreamprofiles/customer/form_cards.phtml')
                ->toHtml();

            $response = array('errors' => $errors, 'success' => count($errors) == 0, 'address_id' => $address->getId(), 'block' => $block, 'payment_block' => $paymentBlock, );
            $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
            $this->getResponse()->setBody(json_encode($response));
            return;

        }


        if(count($errors)) {
            return $errors;
        } else {
            return $address->getId();
        }

    }

    public function deletecardAction()
    {
		$tokenId = $this->getRequest()->getParam('data_key');
		
		try {
			$return = Mage::helper('beanstreamprofiles')->deleteToken($tokenId);
		}
		catch (Exception $e) {
			$error_message = $e->getMessage();
		}

		if(!isset($error_message))
		{
			$this->_getSession()->addSuccess(Mage::helper('beanstreamprofiles')->__('Card deleted Successfully'));
		}
		else
		{
			$this->_getSession()->addError(Mage::helper('beanstreamprofiles')->__($error_message));
		}
		$this->_redirect('*/*/manage');
   }

    public function defaultcardAction()
    {
		$tokenId = $this->getRequest()->getParam('data_key');
		
		try {
			$return = Mage::helper('beanstreamprofiles')->setTokenDefault($tokenId);
		}
		catch (Exception $e) {
			Mage::logException($e);
			$error_message = $e->getMessage();
		}

		if(!isset($error_message))
		{
			$this->_getSession()->addSuccess(Mage::helper('beanstreamprofiles')->__('Card  Successfully Updated'));
		}
		else
		{
			$this->_getSession()->addError(Mage::helper('beanstreamprofiles')->__($error_message));
		}
		$this->_redirect('*/*/manage');
   }

    public function savecardAction()
    {
		$data = $this->getRequest()->getParams();
		$pay_mod = mage::getModel('beanstreamprofiles/PaymentMethod');
		$error_message = "General failure";

        $ajaxResponse = array('success' => false);
        $isAjax = $this->getRequest()->getParam('is_ajax');

		// wrap in try catch add error
		try {
			$return = $pay_mod->createToken($data);
		}
		catch (Exception $e) {
			$error_message = $e->getMessage();
			mage::log(__CLASS__ . " EXCEPTION ". print_r($e->getMessage(),1));
		}

		if($return == 1) {
            $message = Mage::helper('beanstreamprofiles')->__('Card added Successfully');
            if($isAjax) {
                $ajaxResponse['success'] = true;
                $ajaxResponse['message'] = $message;

            } else {
                $this->_getSession()->addSuccess($message);
            }
		} else {
            $message = Mage::helper('beanstreamprofiles')->__((string)($return ? $return : $error_message));
//			$this->_getSession()->addError(Mage::helper('beanstreamprofiles')->__((string)($return->Verbiage[0] ? $return->Verbiage[0] : $error_message)));
            if($isAjax) {
                $ajaxResponse['message'] = $message;
            } else {
                $this->_getSession()->addError($message);

            }
		}

        if($isAjax) {
            $ajaxResponse['message'] = $message;
            $ajaxResponse['block'] = Mage::app()->getLayout()
                ->createBlock('page/html')
                ->setBlockId("bs_cards")
                ->setTemplate('beanstreamprofiles/customer/form_cards.phtml')
                ->toHtml();

            $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
            $this->getResponse()->setBody(json_encode($ajaxResponse));
            return;

        } else {
            $this->_redirect('*/*/manage');
        }
   }

}