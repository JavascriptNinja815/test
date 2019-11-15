<?php

class Collinsharper_Beanstreamprofiles_Model_PaymentMethodstored extends Collinsharper_Beanstreamprofiles_Model_PaymentMethod
{

    protected $_parentcode = 'beanstreamprofiles';
    protected $_code = 'beanstreamprofilesstored';
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
	protected $_formBlockType = 'beanstreamprofiles/form_beanstreamprofilesstored';
    protected $_infoBlockType = 'beanstreamprofiles/info_beanstreamprofilesstored';

	protected $_payment;

	protected function _construct()
    {
        parent::_construct();
    }

    public function getConfigData($field, $storeId = null)
    {
		$code = $this->getCode();
		if($field == 'max_order_total')
		{
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
        //$info->setCcCidEnc($info->encrypt($info->getCcCid()));
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
        return $this->hasStoredData();
    }

	protected function help()
	{
		return Mage::helper('beanstreamprofiles');
	}

    public function hasStoredData()
    {
		$account = $this->help()->getStoredAccountId();
		if($account == false)
		{
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

	private function getpaymentAction()
	{
		return $this->getParentConfig('payment_action');
	}

	public function __authorize(Varien_Object $payment, $amount)
    {
	   	$error = false;

		if($amount<0)
		{
			$error = $this->help()->__('Invalid amount for autorization.');
		}

		if ($error !== false)
		{
			Mage::throwException($error);
		}

		$txnMode = 'preauth';

        $this->_amount = $amount;
		$payment->setAmount($amount);
		$this->_payment = $payment;
		$request = $this->_postRequest($payment, $txnMode);

        if ($request['response'] == 1)
		{
            $payment->setStatus('APPROVED');
			$payment->setCcApproval($request['authcode'])
                ->setLastTransId($request['lasttransid'])
                ->setCcTransId($request['transactionid'])
                //->setCcAvsStatus($request['avsresponse'])
                ->setCcCidStatus($request['cvvresponse']);
	//		$payment->getOrder->setExtOrderId($payment->getCcOwner());
        }
        else
		{
            if ($request['responsetext'])
			{
                $error = $this->help()->__($request['responsetext']);
            }
            else
			{
                $error = $this->help()->__('Error in capturing the payment '.$request['responsetext']);
            }
			$this->_mailFailure($request);
        }

        if ($error !== false)
		{
			$this->log(" we have beanstream error " . print_r($request,1));
            Mage::throwException($error);
        }
        return $this;
    }
	
	public function _dont_need_capture(Varien_Object $payment, $amount)
    {

		$error = false;
		$this->log("in cap ". $payment->getCcTransId() . " and " . $payment->getLastTransId());
		$this->log("in capture with  ".$amount);
		$order = $payment->getOrder();
		$order_id = $order_id =sprintf("%'920d", rand());
		$toid = $order->getIncrementId();

		if ($toid > 0)
		{
			$order_id = $toid;
		}


		if($amount<0)
		{
			$error = $this->help()->__('Invalid amount for capture.');
		}
        if ($error !== false)
		{
			Mage::throwException($error);
		}



		$txnMode  = 0;
		$txRefid = 0;
        if ($payment->getCcTransId() && $payment->getLastTransId())
		{
			$txRefid = $payment->getCcTransId();
			$payment->getCcTransId();
            $txnMode = 'completion';
        }
		else
		{
            $txnMode = 'purchase';
        }

		$this->_amount = $amount;
		$payment->setAmount($amount);
		$this->_payment = $payment;
		
		
		$return = $this->_processStoredPurchase($purchase, $payment->getCcOwner(), $order_id, $amount);

		$this->_customerCode = false;

		if($this->getDebug() || $this->getTest())
		{
			$this->log(__FUNCTION__ . " returned data from purchase " . print_r($return,1));
		}

        if ($return['trnApproved'] == 1)
		{
			$payment->setStatus('APPROVED');
            $payment->setLastTransId($return['trnId']);
            $payment->setCcTransId($return['trnId'])
				->setCcAvsStatus($return['avsMessage'])
				->setCcCidStatus($return['cvdId']);
			return $this;
        }
		
		Mage::throwException($return['messageText']);
	
        return $this;
    }


}