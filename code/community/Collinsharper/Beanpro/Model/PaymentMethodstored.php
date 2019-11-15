<?php

class Collinsharper_Beanpro_Model_PaymentMethodstored extends Collinsharper_Beanpro_Model_PaymentMethod
{

    protected $_parentcode = 'beanpro';
    protected $_code = 'beanprostored';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
	protected $_canRefundInvoicePartial	= true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc 				= true;
    protected $_amount 					= 0;
	protected $_formBlockType = 'beanpro/form_beanprostored';
    protected $_infoBlockType = 'beanpro/info_beanprostored';

	protected $_payment;

	protected function _construct()
    {
        parent::_construct();
    }

    public function getConfigData($field, $storeId = null)
    {
		$code = $this->getCode();
		if($field == 'max_order_total') {
			$code = $this->_parentcode;
		}
		
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/'.$code.'/'.$field;
        return Mage::getStoreConfig($path, $storeId);
    }

	public function getConfig($v)
	{
		return Mage::getStoreConfig('payment/'.$this->_code.'/'.$v);
	}

    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        }
        $info->setCcNumber(null)
            ->setCcCid(null);
        return $this;
    }

	public function canUseInternal()
    {
          return $this->hasStoredData();
    }

    /**
     * Can be used in regular checkout
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        return ($this->isAdmin() && $this->hasStoredData()) ||
            $this->hasStoredData() &&
            (!$this->getConfig('disable_frontend'));
    }

	protected function help()
	{
		return Mage::helper('beanpro');
	}

    public function hasStoredData()
    {
		$account = $this->help()->getStoredAccountId();
		if($account == false) {
			return false;
		}
		return true;
    }

	private function getAccount()
	{
		return $this->getParentConfig('merchant_id');
	}

	private function getTest()
	{
		return $this->getParentConfig('test');
	}

	private function getDebug()
	{
		return $this->getParentConfig('debug');
	}

	public function getPaymentAction()
	{
		return $this->getParentConfig('payment_action');
	}

}
