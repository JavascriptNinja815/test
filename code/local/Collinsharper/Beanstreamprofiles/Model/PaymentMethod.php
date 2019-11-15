<?php

class Collinsharper_Beanstreamprofiles_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc
{

    protected $_code = 'beanstreamprofiles';
    protected $_parentcode = 'beanstreamprofiles';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_customerCode	        = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial	= true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc 				= false;
    protected $_amount 					= 0;
    protected $_formBlockType = 'beanstreamprofiles/form_beanstreamprofiles';
    protected $_infoBlockType = 'beanstreamprofiles/info_beanstreamprofiles';
    const PENDINGVBV = 'pending_beanstreamprofilevbv';
    const SERVICEVERSION = 1.2;
    const ACTION_PURCHASE = 'P';
    const BEANSTREAMTOKENLENGTH = 32;


    protected $_payment;

    protected function _construct()
    {
        parent::_construct();
    }


    public function _isAdminHasProfileForceToken()
    {
        return $this->isAdmin() && ($this->_code == $this->_parentcode) &&
            $this->_payment->getOrder()->getId() && !$this->_payment->getCcNumber() &&
            strlen($this->_payment->getCcOwner()) == self::BEANSTREAMTOKENLENGTH;
    }

    public function isAdmin()
    {
        return Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() == 'adminhtml';
    }

    public function getQuoteTotal()
    {
        $q =  Mage::getSingleton("checkout/cart")->getQuote();
        $amnt = $q->getGrandTotal();
        $this->log(__METHOD__ . __LINE__ . " TTL " . $amnt);
        return $amnt;
    }

    public function canUseForCountry($country)
    {
        if($this->overMaxCheckoutTotal())
        {
            return false;
        }
        return parent::canUseForCountry($country);
    }

    public function overMaxCheckoutTotal()
    {
        return $this->getParentConfig('max_checkout_order_total') > 0 && $this->getQuoteTotal() > $this->getParentConfig('max_checkout_order_total');
    }

    public function canUseCheckout()
    {
        return $this->_canUseCheckout && !$this->overMaxCheckoutTotal();
    }

    public function isSandbox()
    {
        return $this->getConfig('sandbox');
    }

    public function getConfig($v)
    {
        return Mage::getStoreConfig('payment/'.$this->_code.'/'.$v);
    }

    public function getParentConfig($v)
    {
        return Mage::getStoreConfig('payment/'.$this->_parentcode.'/'.$v);
    }

    protected function help()
    {
        return Mage::helper('beanstreamprofiles');
    }

    protected function _getHelper()
    {
        return $this->help();
    }

    protected function log($txt)
    {
        if($this->getDebug() || $this->getTest())
        {
            mage::log($txt);
        }
    }


    private function _getCvv($payment)
    {
        $mpgCvdInfo = false;
        if($this->getConfig('useccv'))
        {
            $cvv = array('cvd_indicator' => '1',
                         'cvd_value' => $payment->getCcCid() );
            $mpgCvdInfo = new mpgCvdInfo($cvv);
        }
        return $mpgCvdInfo;
    }

    private function _getCardHolder($billing)
    {
        return
            (strlen($billing->getFirstname().$billing->getLastname()) !== false ?
                $billing->getFirstname().' '.$billing->getLastname()
                : '');
    }

    public function canRefund()
    {
        return $this->_canRefund;
    }

    public function canVoid(Varien_Object $payment)
    {
        return $this->_canVoid;
    }

    public function canCapturePartial()
    {
        return $this->_canCapturePartial;
    }

    public function canAuthorize()
    {
        return $this->_canAuthorize;
    }

    public function canCapture()
    {
        return $this->_canCapture;
    }

    private function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function getAccount()
    {
        return $this->getParentConfig('merchant_id');
    }

    private function getApicode()
    {
        return $this->getParentConfig('apikey');
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
        return $this->getConfig('payment_action');
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $this->getCheckoutSession()->setBeanstreamRedirect(false);
        $PaymentData = new Varien_Object;
        // decide if its capture or PAC
        $PaymentData->txRefid = false;
        $PaymentData->txnMode = 'PA';
        $PaymentData->amount = $amount;

        $error = false;
        if($this->getDebug() || $this->getTest())
        {
            $this->log("in Auth ". $payment->getCcTransId() . " and " . $payment->getLastTransId());
            $this->log("in Auth with  ".$amount);
        }

        if($amount<0)
        {
            $error = $this->help()->__('Invalid amount for authorization.');
        }

        if ($error !== false)
        {
            Mage::throwException($error);
        }

        $this->_amount = $amount;
        $this->_payment = $payment;
        //$payment->setAmount($amount);

        $username = '';
        $password = '';
        $error = false;
        $post_vars = '';

        $order = $payment->getOrder();
        $order_id = (rand(0, time())).rand();
        $toid =  substr($order->getIncrementId().'aa'.rand(),0,30);

        $billing = $order->getBillingAddress();
        if (empty($billing)) {
            $error = Mage::helper('paygate')->__('Invalid billing data.');
        }

        // if we are parent we create account - if we are olverlaoded we use CCowner
        if(!$this->_isAdminHasProfileForceToken() && $this->_code == $this->_parentcode) {
            $postdata = $this->_buildPost($payment, $order, $order_id);
            $request = $this->_createAccount($payment, $postdata);

            if($request !== true) {
                mage::log(__METHOD__ . __LINE__ );
                Mage::throwException($this->_getHelper()->__($request));
            }

            if($this->getDebug() || $this->getTest()) {
                $this->log(__METHOD__ . " returned data " . print_r($request,1));
            }

        } else {
            $this->_customerCode = $payment->getCcOwner();
        }

        $return = $this->_processStoredPurchase($payment, $this->_customerCode, $order_id, $PaymentData);

        $this->_customerCode = false;

        if($this->getDebug() || $this->getTest())
        {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__ . " returned data from purchase " . print_r($return,1));
        }


        if($return['responseType'] == 'R')
        {
            if($this->getDebug() || $this->getTest())
            {
                mage::log(__CLASS__ . __LINE__ ." we have qid" . $payment->getOrder()->getQuoteId());
            }
            $this->getCheckoutSession()->setBeanstreamQuoteId($payment->getOrder()->getQuoteId());
            $this->getCheckoutSession()->setBeanstreamRedirect($return['pageContents']);
            $payment->setStatus('APPROVED');
            $payment->setLastTransId('pending');
            $payment->setCcTransId('pending')
                ->setCcAvsStatus(0)
                ->setIsTransactionClosed(0)
                ->setCcCidStatus(0);
            $this->getCheckoutSession()->setBeanstreamOrderId($order->getId());
            return $this;
        }
        elseif ($return['trnApproved'] == 1)
        {
            $payment->setStatus('APPROVED');
            $payment->setLastTransId($return['trnId']);
            $payment->setCcTransId($return['trnId'])
                ->setCcApproval($return['authCode'])
                ->setCcAvsStatus($return['avsMessage'])
                ->setCcCidStatus($return['cvdId']);
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ );
            return $this;
        }
        $this->_mailFailure($return);
        Mage::throwException($return['messageText']);

    }

    public function _buildPost($payment, $order, $order_id)
    {
    	$billing = $order->getBillingAddress();

        $email = "";

        if($order->getCustomerEmail() != "")
        {
            $email = $order->getCustomerEmail();
        }
        else
        {
            $email = $billing->getEmail();
        }

        $postdata = array(
            'serviceVersion' =>  1,
            'merchantId' => $this->getAccount(),
            'operationType' => 'N', // M to change one?
            'passCode' => $this->getApicode(),
            'responseFormat' => 'XML',
            'trnOrderNumber' => $order_id,
            'status' => 'A', //M
            'trnCardOwner' =>  $billing->getFirstname() . ' ' .$billing->getLastname(),
            'trnCardNumber' => $payment->getCcNumber(),
            'trnExpMonth' => sprintf('%02d',  $payment->getCcExpMonth()) ,
            'trnExpYear' => substr(sprintf('%04d',  $payment->getCcExpYear()), -2) ,
            'cardValidation' => 1,
            'trnCardCvd' => $payment->getCcCid() ,
            'ordName' => $billing->getFirstname() . ' ' .$billing->getLastname(),
            'ordAddress1' => $billing->getStreet(1) ,
            'ordCity' => $billing->getCity(),
            'ordProvince' => ((($billing->getCountry() == 'CA' || $billing->getCountry() == 'US' ) && $billing->getRegionCode()) ? $billing->getRegionCode() : "--"),
            'ordCountry' => $billing->getCountry(),
            'ordPostalCode' => $billing->getPostcode(),
            'ordEmailAddress' => $email,
            'ordPhoneNumber' => $billing->getTelephone(),
        );

        //mage::log(__CLASS__ . __LINE__ . " we have cav details " . print_r(Mage::app()->getRequest()->getParams(),1));
        if($this->getConfig('cav_enabled'))
        {
            $_post_payment = Mage::app()->getRequest()->getParam('payment');
            $postdata['cavEnabled'] = 1;
            $postdata['cavServiceVersion'] = self::SERVICEVERSION;


            if($this->getConfig('cav_dob'))
            {
                $dob = isset($_post_payment['beanstreamprofiles']) && isset($_post_payment['beanstreamprofiles']['cc_dob']) ? $_post_payment['beanstreamprofiles']['cc_dob'] : false;
                $dob_parts = explode("/",$dob);
                if(!$dob || !stristr($dob,"/") || count($dob_parts) != 3)
                {
                    $error = Mage::helper('paygate')->__('Invalid Date of Birth.');
                    Mage::throwException($error);
                }
                // ensure you dont transpose the MM and DD
                $postdata['cavBirthMonth'] = $dob_parts[0];
                $postdata['cavBirthDay'] = $dob_parts[1];
                $postdata['cavBirthYear'] = $dob_parts[2];
                $dob_parts = $dob = false;
            }

            if($this->getConfig('cav_sin'))
            {
                $sin = isset($_post_payment['beanstreamprofiles']) && isset($_post_payment['beanstreamprofiles']['cc_sin']) ? str_replace('-','',$_post_payment['beanstreamprofiles']['cc_sin']) : false;
                if(!$sin || strlen($sin) != 9)
                {
                    $error = Mage::helper('paygate')->__('Invalid SIN number.');
                    Mage::throwException($error);
                }
                $postdata['cavSIN'] = $sin;
                $sin = false;
            }




            $_post_payment = false;
        }

        return $postdata;
    }


    public function capture(Varien_Object $payment, $amount)
    {
        $this->getCheckoutSession()->setBeanstreamRedirect(false);
        $PaymentData = new Varien_Object;
        // decide if its capture or PAC
        $PaymentData->txRefid = false;
        $PaymentData->txnMode = 'P';

        if ($payment->getCcTransId() && $this->getParentConfig('payment_action') == 'authorize') {
            $PaymentData->txRefid = $payment->getCcTransId();
            $PaymentData->txnMode = 'PAC';
        }

        $error = false;
        if($this->getDebug() || $this->getTest()) {
            $this->log("in cap ". $payment->getCcTransId() . " and " . $payment->getLastTransId());
            $this->log("in capture with  ".$amount);
        }

        mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . " quote ttl " . $this->getQuoteTotal());
        if(ceil($amount) <= 0) {
            $amount = $this->getQuoteTotal();
            mage::log(__CLASS__ . __LINE__ . "FORCING AMOUNT TO QUOTE" .$amount);
            if(ceil($amount) <= 0) {
                $error = $this->help()->__('Invalid amount for capture.');
            }
        }

        if ($error !== false) {
            Mage::throwException($error);
        }

        $PaymentData->amount = $amount;
        $this->_amount = $amount;
        $this->_payment = $payment;
        //$payment->setAmount($amount);

        $username = '';
        $password = '';
        $error = false;
        $post_vars = '';

        $order = $payment->getOrder();
        $order_id = (rand(0, time()));
        $toid =  substr($order->getIncrementId().'aa'.rand(),0,30);
        if ($toid > 0) {
            $order_id = $toid;
        }

        if($PaymentData->txRefid != false) {
            $order_id = $order->getId();
        }

        $billing = $order->getBillingAddress();
        if (empty($billing)) {
            $error = Mage::helper('paygate')->__('Invalid billing data.');
        }


        if ($error !== false) {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
            Mage::throwException($error);
        }

		    $postdata = $this->_buildPost($payment, $order, $order_id);


        if(!$this->_isAdminHasProfileForceToken() && $this->_code == $this->_parentcode) {

            if(!$PaymentData->txRefid) {
                $request = $this->_createAccount($payment, $postdata);

                if($request !== true) {
                    mage::log(__METHOD__ . __LINE__ .print_r($request,1) );
                    Mage::throwException($this->_getHelper()->__($request));
                }

                $payment->setCcOwner($this->_customerCode);


                if($this->getDebug() || $this->getTest()) {
                    $this->log(__METHOD__ . __LINE__ . " returned data " . print_r($request,1));
                }
            }
        } else {

            if($this->_isAdminHasProfileForceToken()) {
                $this->log(__METHOD__ . __LINE__ . " forcing stored payment ");
            }

            $this->_customerCode = $payment->getCcOwner();
        }

        $return = $this->_processStoredPurchase($payment, $this->_customerCode, $order_id, $PaymentData);



        if($this->getDebug() || $this->getTest())
        {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__  . " returned data from purchase " . print_r($return,1));
        }


        if($return['responseType'] == 'R')
        {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__ );
            if($this->getDebug() || $this->getTest())
            {
                mage::log(__CLASS__ . __LINE__ ." we have qid" . $payment->getOrder()->getQuoteId());
                mage::log(__CLASS__ . __LINE__ ." DATA " . print_r($return,1));

            }
            $this->getCheckoutSession()->setBeanstreamQuoteId($payment->getOrder()->getQuoteId());
            $this->getCheckoutSession()->setBeanstreamRedirect($return['pageContents']);
            $payment->setStatus('APPROVED');
            $payment->setLastTransId('pending');
            $payment->setCcTransId('pending')
                ->setCcAvsStatus(0)
                ->setIsTransactionClosed(0)
                ->setCcCidStatus(0);
            $this->getCheckoutSession()->setBeanstreamOrderId($order->getId());
            $this->_payment = false;
            return $this;
        }
        elseif ($return['trnApproved'] == 1)
        {

            $this->log(__CLASS__ . __FUNCTION__ . __LINE__  . " FINAL " .  print_r($return, 1));
            $payment->setStatus('APPROVED');
            $payment->setLastTransId($return['trnId']);
            $payment->setCcTransId($return['trnId'])
                ->setCcAvsStatus($return['avsMessage'])
                ->setCcCidStatus($return['cvdId']);
            $this->_payment = false;
            if(isset($return['capture_return'])) {
                $payment->setLastTransId($return['capture_return']['trnId']);
                $payment->setCcTransId($return['capture_return']['trnId'])
                    ->setCcAvsStatus($return['capture_return']['avsMessage'])
                    ->setCcCidStatus($return['capture_return']['cvdId']);
            }
            $this->_customerCode = false;
            return $this;
        }
        $this->log(__CLASS__ . __FUNCTION__ . __LINE__ );
        $this->_mailFailure($return);
        $return_message = $this->_makeReturnMessage($return);
        //Mage::throwException($this->_getHelper()->__($return['messageText']));
        Mage::throwException($return_message);

    }

    public function _makeReturnMessage($data)
    {
        $help = mage::helper('beanstreamprofiles/messages');

        $return = $this->_getHelper()->__($data['messageText']);
        $available_messages = array(
            'rspCodeCredit1' => 'getrspCodeAddr',
            'rspCodeCredit2' => 'getrspCodeAddr',
            'rspCodeCredit3' => 'getrspCodeAddr',
            'rspCodeCredit4' => 'getrspCodeAddr',
            'rspCodeAddr1' => 'getrspCodeAddr',
            'rspCodeAddr2' => 'getrspCodeAddr',
            'rspCodeAddr3' => 'getrspCodeAddr',
            'rspCodeAddr4' => 'getrspCodeAddr',
            'rspCodeDob' => 'getrspCodeAddr',
            'rspCodeSafeScan' => 'gerspCodeSafeScan',
            'rspCodeSafeScanId' => 'getrspCodeSafeScanId'
        );
        foreach($available_messages as $messageKey => $accessor)
        {
            if(isset($data[$messageKey]) && $help->$accessor($data[$messageKey]))
            {
                mage::log(__CLASS__ .__LINE__ . "adding ". $help->$accessor($data[$messageKey]));
                $return .= "<br />".$help->$accessor($data[$messageKey]);
            }
        }
        mage::log(__CLASS__ .__LINE__ . " returning ". $return);
        return $return;
    }

    public function _mailFailure($return)
    {
        try {
            $mail_to =  array(Mage::getStoreConfig('trans_email/ident_general/email'));
            if($this->getConfig('failure_emails'))
            {
                if(trim($this->getConfig('failure_emails')))
                {
                    $mail_to[] = trim($this->getConfig('failure_emails'));
                }
            }

            mage::log(__CLASS__ . __FUNCTION__ . " sending fail mail ". print_r($return,1));
            // we dont need to send these in debug
            //$return->ref1 == '';
            if(is_object($return))
            {
                $return->ref2 = 'removed for security';
            }

            mage::log(__CLASS__ . __FUNCTION__ . " sending fail mail ". print_r($return,1));

            foreach($mail_to as $m)
            {
                mail($m,'failure in beanstream payment','failure in beanstream payment'. print_r($return,1));
            }

        } catch (Exception $e) { Mage::logException($e); }

    }

    private function getTermUrl()
    {
        return Mage::getUrl('beanstreamprofiles/payment/return', array('_secure' => true));
    }

    //public function getCheckoutRedirectUrl()
    public function getOrderPlaceRedirectUrl()
    {
        //mage::log(__CLASS__ . __FUNCTION__ . "redirect bits " . print_r($this->getCheckoutSession()->getBeanstreamRedirect(),1));
        if($this->getCheckoutSession()->getBeanstreamRedirect() && $this->getCheckoutSession()->getBeanstreamRedirect() !== false && $this->getCheckoutSession()->getBeanstreamRedirect() != '')
        {
            return Mage::getUrl('beanstreamprofiles/payment/redirect', array('_secure' => true));
        }
        return false;
    }


    public function _processStoredPurchase($payment, $customerCode, $orderId, $PaymentData)
    {
        // load Bday from the DB
        $_apostdata = array();

        if($this->getConfig('cav_dob') || $this->getConfig('cav_sin'))
        {
            $_apostdata['cavEnabled'] = 1;
            $_apostdata['cavServiceVersion'] = self::SERVICEVERSION;
        }

        if($this->getConfig('cav_dob'))
        {
            $_apostdata['cavBirthYear'] = '0000';
            $_apostdata['cavBirthMonth'] = '00';
            $_apostdata['cavBirthDay'] = '00';

            $dob = $this->help()->getTokenData($customerCode);
            if(isset($dob['dob']) || $dob['dob'] != '')
            {
                $dob_parts = explode("-",$dob['dob']);
                if(count($dob_parts) == 3)
                {
                    $_apostdata['cavBirthYear'] = $dob_parts[0];
                    $_apostdata['cavBirthMonth'] = $dob_parts[1];
                    $_apostdata['cavBirthDay'] = $dob_parts[2];
                }
            }
        }

        if($this->getConfig('cav_sin'))
        {
            $_apostdata['cavSin'] = '000000000';
            // load SIN ffrom beanstre
            $sin = $this->help()->getTokenDataSinFromBs($customerCode, $dob['trnOrderNumber']);
            $_apostdata['cavSin'] = Mage::helper('core')->decrypt($sin);
            $sin = false;
        }

        // trnType we always run as PA
        $mode = $PaymentData->txnMode;
        if($mode != 'PAC') {
            $mode = 'PA';
        }


       if($this->getParentConfig('purchase_action')) {
            $mode = 'P';
        }

        $postdata = array(
            'merchant_id' => $this->getAccount(),
            'requestType' => 'BACKEND',
            'trnOrderNumber' => $orderId,
            'trnAmount' => $PaymentData->amount,
            'errorPage' => 'na',
            'responseFormat' => 'XML',
            //'trnType' => $PaymentData->txnMode,
            'trnType' => $mode,
            'username' => $this->getParentConfig('login'),
            'password' => $this->getParentConfig('password'),
            'termUrl' => urlencode($this->getTermUrl()),
        );

        // we wouldnt have / use this ffor pre auth cap
        // we add in the CAV details here
        if($customerCode)
        {
            $postdata['customerCode'] = $customerCode; // profile id
            foreach($_apostdata as $k => $v)
            {
                $postdata[$k] = $v;
            }
        }

        if($PaymentData->txRefid)
        {
            $postdata['adjId'] = $PaymentData->txRefid;
        }
        // else
        // {
        // $postdata['customerCode'] = $customerCode;
        // }
        // http_build_query killed the url?
        $_pdata = '';
        foreach($postdata as $k => $v)
        {
            $_d = "{$k}={$v}";
            if($k != 'termUrl')
            {
                $_d = str_replace("&","&amp;","{$k}={$v}");
            }

            $_pdata .= "&".$_d;
        }

        $_pdata = ltrim($_pdata,"&");

        if($this->getDebug() || $this->getTest())
        {
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r( $postdata,1));
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r( $_pdata,1));
        }
        $url = "https://www.beanstream.com/scripts/process_transaction.asp";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST,1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($postdata));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$_pdata);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $qresult = curl_exec($ch);
        parse_str($qresult,$return);
        if($this->getDebug() || $this->getTest())
        {
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($qresult,1));
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($return,1));
        }
        return $this->testFailCvvAvsResponse($payment, $return, $PaymentData);
    }


    public function testFailCvvAvsResponse($payment, $return, $PaymentData)
    {
        // if the transaction was not approved - jsut return we dont have to worry about it
        // if it was a preauth Capture we just return as this is not valid

        $tmp = '';
        if (isset($return['trnApproved'])) {
            $tmp = $return['trnApproved'];
        }

        if($tmp != 1 || $PaymentData->txnMode == 'PAC')
        {
            return $return;
        }

        $this->log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($return,1));
        $order = $payment->getOrder();
        $cancel_transaction_return_failure = false;
        $_cvv_response = isset($return['cvdId']) ? $return['cvdId'] : false;
        $_avs_response = isset($return['avsId']) ? $return['avsId'] : false;
        $this->log(__CLASS__ . __FUNCTION__ . __LINE__ ."  CVV list " . $this->getParentConfig('cvv_approval_list') ." comparing " . $_cvv_response);
        $this->log(__CLASS__ . __FUNCTION__ . __LINE__ ."  AVS list " . $this->getParentConfig('avs_approval_list') . $_avs_response);

        if($this->getParentConfig('cvv_approval_list') && $this->getParentConfig('cvv_approval_list') != '')
        {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
            $list = explode(",",$this->getParentConfig('cvv_approval_list'));
            if(!in_array($_cvv_response, $list))
            {
                $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
                $cancel_transaction_return_failure = 'cvv';
            }
        }

        if(!$cancel_transaction_return_failure && $this->getParentConfig('avs_approval_list') && $this->getParentConfig('avs_approval_list') != '')
        {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
            $list = explode(",",$this->getParentConfig('avs_approval_list'));
            if(!in_array($_avs_response, $list))
            {
                $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
                $cancel_transaction_return_failure = 'avs';
            }
        }

        if($cancel_transaction_return_failure !== false)
        {
            $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
            $order->addStatusToHistory(
                $order->getStatus(), // keep order status/state
                $this->help()->__('Payment Failed ' .$cancel_transaction_return_failure, true),
                $notified = false
            );

            $this->log(__CLASS__ . __FUNCTION__ . __LINE__ . "Failing Transaction due to {$cancel_transaction_return_failure} failure");

            // add comment to the order
            // void the transaction
            $return['messageId'] = '99999999';
            $return['trnApproved'] = '99999999';
            $return['messageText'] = $this->help()->__('Merchant_Declined_'.$cancel_transaction_return_failure);
            if($cancel_transaction_return_failure == 'avs')
            {
                $return['messageText'] = $this->help()->__('Merchant_Declined_'.$return['avsMessage']);

            }
	
         if($this->getParentConfig('purchase_action')) {
                    // TODO delete the profile
                    /*
                     * DELETE_PROFILE Request Fields
Variable        Required/Optional       Type/Length     Description
serviceVersion  R       A/N     Must be set to 1.1
operationType   R       A/N     Must be “DELETE_PROFILE”
merchantId      R       N 9 digits      Merchant ID for account in Beanstream
passCode        O       Up to 32 A/N    Either this or hashValue must be passed, according to settings in Payment Profile Configuration Screen
hashValue       O       A/N (QS Only)   Either this or passCode must be passed, according to settings in Payment Profile Configuration ScreencustomerCode    R       Up to 32 A/N    Must match existing customerCode
responseFormat  R       A/N     Will be XML for XML formatted response, QS for a querystring based response.

                     */
                    $voidPayment = new Varien_Object;
                    $voidPayment->setVoidTransactionId($return['trnId']);
                    $voidPayment->setOrder($payment->getOrder());
                    $vreturn = $this->void($voidPayment);

                    // TODO we should turn the profile here
                }

            // we always preauth since we are canceling
            // we will just ignore it and let it fall off
            //	$voidPayment = new Varien_Object;
            //	$voidPayment->setVoidTransactionId($return['trnId']);
            //	$voidPayment->setOrder($payment->getOrder());
            $vreturn = array();
            try {
                //	$vreturn = $this->void($voidPayment);
                $this->log(__CLASS__ . __FUNCTION__ . __LINE__);
                $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    //$this->help()->__('Payment successfully voided due to AVS / CVV failure TxId: '.$vreturn['trnId'], true),
                    $this->help()->__('Payment successfully voided due to AVS / CVV failure TxId: ', true),
                    $notified = false
                );
                $return['void_data'] = $vreturn;
            } catch (Exception $e) {
                // if the void failed we have a problem
                mage::log(__CLASS__ . __FUNCTION__  . " Failed to void transaction after failed avs / cvv data  " .$e->getMessage());
                $return['internal_message'] = " Voiding the transaction after a failed AVS / CVV value did not succeed";
                $this->_mailFailure($return);
                $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    $this->help()->__('Failed to void payment - please manually check with the merchant the payment is canceled.', true),
                    $notified = false
                );
            }
        }

        // we have to capture it if its sucecssful
      if($cancel_transaction_return_failure == false &&
            $PaymentData->txnMode != 'PA' && !$this->getParentConfig('purchase_action')) { 

            $fpayment = $capturePayment = new Varien_Object;
            $capturePayment->setTxRefid($return['trnId']);
//				$capturePayment->setOrder($payment->getOrder());
            $capturePayment->setAmount($PaymentData->amount);
            $capturePayment->setTxnMode('PAC');
            //$captureAuth = $this->capture($capturePayment, $PaymentData->amount);
            $captureAuth = $this->_processStoredPurchase($fpayment, $this->_customerCode, $return['trnOrderNumber'], $capturePayment);
            //			$capturePayment->setOrder(false);

            $return['capture_return'] = $captureAuth;
            if($captureAuth['trnApproved'] != 1)
            {
                // failed to capture the auth
                $this->_mailFailure($captureAuth);
                $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    $this->help()->__('Failed to capture payment - please manually check with the merchant the payment was successful.', true),
                    $notified = false
                );

                $return['messageId'] = '99999999';
                $return['trnApproved'] = '99999999';
                $return['messageText'] = $this->help()->__('Merchant_Declined_failed_to_process_payment');

            }

            // run the capture on the transacvtion

        }

        return $return;
    }


    public function _createAccount($payment, $postdata, $inboundPostData=array())
    {

     if($this->getParentConfig('disable_profile_card_verification')) {
            $this->log(__METHOD__  . " Disabling card verification on account create.." );
            $postdata['cardValidation'] = 0;
        }

        $order = $payment->getOrder();

        $url = "https://www.beanstream.com/scripts/payment_profile.asp";

        if(isset($postdata['cavSIN']))
        {
            // Encrypt it.
            $postdata['ref2'] = Mage::helper('core')->encrypt($postdata['cavSIN']);
            unset($postdata['cavSIN']);

        }

        if(isset($postdata['cavBirthDay']) && isset($postdata['cavBirthMonth']) && isset($postdata['cavBirthYear']))
        {
            $postdata['ref1'] = $postdata['cavBirthYear'].'-'.$postdata['cavBirthMonth'].'-'.$postdata['cavBirthDay'];
            unset($postdata['cavBirthYear']);
            unset($postdata['cavBirthMonth']);
            unset($postdata['cavBirthDay']);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($postdata));
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $qresult = curl_exec($ch);

        if($this->getDebug() || $this->getTest())
        {
            $this->log(__FUNCTION__ . " sent data " . print_r($postdata,1));
            $this->log(__FUNCTION__ . " returned data " . print_r($qresult,1));
        }

        $data = simplexml_load_string($qresult);
        $this->help()->recordTrxData($data);
        $postdata['trnOrderNumber'] = $data->cardVerification->trnOrderNumber;
        if(property_exists($data, 'responseCode') && $data->responseCode == 1)
        {
            //mage::log(__CLASS__ . "storing toking " );
            $card = substr($data->trnCardNumber,-4);
            $cc_exp = sprintf('%02d',  $payment->getCcExpMonth()). substr($payment->getCcExpYear(),-2);
            $this->_dbinsert ($this->help()->_getTable(), $data->customerCode,  $order, str_pad($card,4,"0"), $cc_exp, $postdata, $inboundPostData );
            $this->_customerCode = (string)$data->customerCode[0];
            return true;
        }
        $this->_mailFailure($data);
        mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($data,1));
        $return = $data->responseMessage;
        if(is_object($data->cardVerification) && is_object($data->cardVerification->messageText))
        {
            // KL: Fix for ticket 759 - Blank message popping up when duplicate submission happenned.
            if (strlen($data->cardVerification->messageId) > 0)
            {
                $return = $this->_getHelper()->__($data->cardVerification->messageText.' '.$data->cardVerification->messageId);
            }
        }
        return $return;
    }

    function _createFormAddress($formData)
    {
        $street = $formData->getData('bsform_street');
        $street = $street[0];
        $billing = mage::getModel('customer/address');
        $billing->setData('is_active', 1);
        $billing->setData('firstname', $formData->getData('bsform_firstname'));
        $billing->setData('lastname', $formData->getData('bsform_lastname'));
        $billing->setData('company', $formData->getData('bsform_company'));
        $billing->setData('city', $formData->getData('bsform_city'));
        $billing->setData('country_id', $formData->getData('bsform_country_id'));

        if($formData->getData('bsform_region_id')) {
            $region = Mage::getModel('directory/region')->load($formData->getData('bsform_region_id'));
            $billing->setData('region', $region->getCode());
        }

        $billing->setData('region_id', $formData->getData('bsform_region_id'));
        $billing->setData('postcode', $formData->getData('bsform_postcode'));
        $billing->setData('telephone', $formData->getData('bsform_telephone'));
        $billing->setData('street', $street);
        return $billing;
    }

    public function createToken($data)
    {
        $formData = new Varien_Object($data);

        if($this->getTest() || $this->getDebug()) {
            $this->log(__CLASS__ . "running" 	. __LINE__  . print_r($data,1)."\n");
        }

        $session = mage::getSingleton('customer/session');

        $email = $session->getCustomer()->getEmail();
        $customerId = $session->getCustomer()->getId();
        $addressId = isset($data['billing_address_id']) ? $data['billing_address_id'] : false;
        if(!$addressId &&  isset($data['bs_card-address-select'])) {
		$addressId = isset($data['bs_card-address-select']) ? $data['bs_card-address-select'] : false;
	}
	if(!$addressId && isset($data['shipping_address_id'])) {
		$addressId = $data['shipping_address_id'];
	}
        if(!$addressId && $customerId) {
            // create it
            $formData = new Varien_Object($data);

            if($formData->getData('bsform_firstname')) {
                $billing = $this->_createFormAddress($formData);
                $billing
                    ->setParentId($customerId)
                ->setIsDefaultBilling(true)
                    ->setIsDefaultShipping(true);

                $billing->save();
                $addressId = $billing->getId();
            }

            if(!$addressId) {
                throw new Exception("Unable to load the address");
            }
        }

        if($addressId ) {
            $shipping = $billing = mage::getModel('customer/address')->load($addressId);
        } else {


            $billing = $this->_createFormAddress($formData);
            $email = $formData->getData('bsform_email');
            $customerId = rand();
            $shipping = $billing;
        }
        $data['billing_address'] = $billing;
        //mage::log(__CLASS__ . __FUNCTION__  . " customer id  " . $session->getCustomerId());
        //mage::log(__CLASS__ . __FUNCTION__  . " customer id  " . $billing->getCustomerId());

        $info = new Mage_Payment_Model_Info;
        $info->setCcNumber($data['payment']['cc_number']);

        $info->setCcType($data['payment']['cc_type']);
        $info->setCcExpYear($data['payment']['cc_exp_year']);
        $info->setCcExpMonth($data['payment']['cc_exp_month']);
        $info->setCcCid($data['payment']['cc_cid']);
        $this->setData('info_instance',$info);

        $this->createValidate($data);

        if($session->getCustomerId() != $billing->getCustomerId())
        {
            mage::log(__CLASS__ . __FUNCTION__  . " we have mismatched customer data ");
        }

        $payment = new Varien_object;
        $order = new Varien_Object;

        $order_id = rand();
        $order->setCustomerEmail($email);
        $order->setBillingAddress($billing);
        $order->setCustomerId($customerId);
        $payment->setCcNumber($data['payment']['cc_number']);
        $payment->setCcExpMonth($data['payment']['cc_exp_month']);
        $payment->setCcExpYear($data['payment']['cc_exp_year']);

        $payment->setCcType($data['payment']['cc_type']);

        $payment->setCcCid($data['payment']['cc_cid']);
        $order->setId($order_id);
        $payment->setOrder($order);
        $this->_payment = $payment;
        Mage::log(__CLASS__ . __FUNCTION__ . __LINE__);
        $postdata = $this->_buildPost($payment, $order, $order_id);
        Mage::log(__CLASS__ . __FUNCTION__ . __LINE__);
        $request = $this->_createAccount($payment, $postdata, $data);
        //mage::log(__CLASS__ . " returning " . print_r($request,1));
        return $request;
    }

    public function authorizeDeposit($payment, $amount)
    {
        //		$session = Mage::getSingleton('checkout/session');
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $order = new Varien_Object;
        $order->setIncrementId(0);
        $order->setId(rand(0, time()));
        $order->setBillingAddress($quote->getBIllingAddress());
        $order->setCustomerEmail($quote->getCustomerEmail());

        //$payment = new Varien_Object;
        $payment->setData($this->getData('info_instance'));
        $info = $this->getData('info_instance');
        $payment->setOrder($order);
        $payment->setAmount($amount);
        $payment->setCcOwner($info->getCcOwner());
        $payment->setCcNumber($info->getCcNumber());
        $payment->setCcType($info->getCcType());
        $payment->setCcExpYear($info->getCcExpYear());
        $payment->setCcExpMonth($info->getCcExpMonth());
        $payment->setCcCid($info->getCcCid());
        $ret =  $this->authorize($payment,$amount);
        mage::log("we have somem buits " . $payment->getStatus());
        return $ret;
    }

    public function setInfoInstance($data)
    {
        $this->setData('info_instance',$data);
    }

    public function createValidate($data)
    {

        if (!$this->canUseForCountry($data['billing_address']->getCountryId()))
        {
            Mage::throwException($this->_getHelper()->__('Selected payment type is not allowed for billing country.'));
        }

        $info = $this->getInfoInstance();
        $errorMsg = false;
        $availableTypes = explode(',',$this->getConfigData('cctypes'));
        //mage::log("TESTI?NG FOR CC TYPE " . print_r($availableTypes,1));
        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';



        if (in_array($info->getCcType(), $availableTypes)){
            if ($this->validateCcNum($ccNumber)
                // Other credit card type number validation
                || ($this->OtherCcType($info->getCcType()) && $this->validateCcNumOther($ccNumber))) {

                $ccType = 'OT';
                $ccTypeRegExpList = array(
                    //Solo, Switch or Maestro. International safe
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/', // Solo only
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',

                    'SS'  => '/^((6759[0-9]{12})|(6334|6767[0-9]{12})|(6334|6767[0-9]{14,15})|(5018|5020|5038|6304|6759|6761|6763[0-9]{12,19})|(49[013][1356][0-9]{12})|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$/', // Maestro / Solo
                    'VI'  => '/^4[0-9]{12}([0-9]{3})?$/',             // Visa
                    'MC'  => '/^5[1-5][0-9]{14}$/',                   // Master Card
                    'AE'  => '/^3[47][0-9]{13}$/',                    // American Express
                    'DI'  => '/^6011[0-9]{12}$/',                     // Discovery
                    'JCB' => '/^(3[0-9]{15}|(2131|1800)[0-9]{11})$/', // JCB
                );

                foreach ($ccTypeRegExpList as $ccTypeMatch=>$ccTypeRegExp) {
                    if (preg_match($ccTypeRegExp, $ccNumber)) {
                        $ccType = $ccTypeMatch;
                        break;
                    }
                }

                if (!$this->OtherCcType($info->getCcType()) && $ccType!=$info->getCcType()) {
                    $errorCode = 'ccsave_cc_type,ccsave_cc_number';
                    $errorMsg = $this->_getHelper()->__('Credit card number mismatch with credit card type.');
                }
            }
            else {
                $errorCode = 'ccsave_cc_number';
                $errorMsg = $this->_getHelper()->__('Invalid Credit Card Number');
            }

        }
        else {
            $errorCode = 'ccsave_cc_type';
            $errorMsg = $this->_getHelper()->__('Credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
            if (!$info->getCcCid() || !$regExp || !preg_match($regExp ,$info->getCcCid())){
                $errorMsg = $this->_getHelper()->__('Please enter a valid credit card verification number.');
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorCode = 'ccsave_expiration,ccsave_expiration_yr';
            $errorMsg = $this->_getHelper()->__('Incorrect credit card expiration date.');
        }

        if($errorMsg){
            Mage::throwException($errorMsg);
            //throw Mage::exception('Mage_Payment', $errorMsg, $errorCode);
        }


        return $this;
    }

    protected function _implodetoXml($txnArray)
    {
        $return = '';
        foreach($txnArray as $k => $v)
        {
            $return .= "<{$k}>{$v}</{$k}>\n";
        }
        return $return;
    }


    public function refund(Varien_Object $payment, $amount)
    {

        $error = false;
        $this->_amount = $amount;
        //$payment->SetAmount($amount);
        if ($payment->getRefundTransactionId() && $amount>0) {
            $this->_payment = $payment;
            // hard coded for PAYMENT_ACTION_CANCEL_PAYMENT for now
			
           // $txnRequest = $this->_buildRequest($payment,'R','100000007aa1567593056');
            $txnRequest = $this->_buildRequest($payment,'R',$payment->getRefundTransactionId());
            $request = $this->_postRequest('R', $txnRequest);

            if ($request['trnApproved']==1) {
                $payment->setStatus('APPROVED');
            } else {
                $error = $request['description'];
            }

        } else {
            $error = Mage::helper('paygate')->__('Error in refunding the payment');
        }

        if ($error !== false) {
            Mage::throwException($error);
        }
        return $this;
    }

    public function void(Varien_Object $payment)
    {
        $this->log(__FUNCTION__ . __LINE__);
        $error = false;
        if($payment->getVoidTransactionId())
        {
            $this->log(__FUNCTION__ . __LINE__);
            $txmode = 'VP';
            $this->_payment = $payment;
            $txnRequest = $this->_buildRequest($payment,$txmode,$payment->getVoidTransactionId()); // hard coded for PAYMENT_ACTION_CANCEL_PAYMENT for now
            $request = $this->_postRequest($txmode, $txnRequest);

            if($this->getDebug() || $this->getTest())
            {
                $this->log(__FUNCTION__ . " sent data " . print_r($txnRequest,1));
                $this->log(__FUNCTION__ . " returned data " . print_r($request,1));
            }

            if($request['trnApproved']==1){
                $payment->setStatus('APPROVED' );
            }
            else{
                $payment->setStatus(self::STATUS_ERROR);
                $error = $request['description'];
            }
        }else{
            $this->log(__FUNCTION__ . __LINE__);
            $payment->setStatus(self::STATUS_ERROR);
            $error = Mage::helper('paygate')->__('Invalid transaction id');
        }
        if ($error !== false) {
            $this->log(__FUNCTION__ . __LINE__);
            Mage::throwException($error);
        }
        return $this;
    }


    protected function _buildRequest(Varien_Object $payment, $requestType, $txRefid = 0)
    {

        $adjId = '';
        $username = '';
        $password = '';
        $error = false;
        $post_vars = '';

        if($this->getParentConfig('login'))
        {
            $username = '&username='. $this->getParentConfig('login');
            $password = '&password='. $this->getParentConfig('password');
        }

        $order = $payment->getOrder();
        $order_id = microtime() . rand(0, time());
        $toid = $order->getIncrementId();
        //if ($toid > 0)  $order_id = $toid;

        if($txRefid != 0) {
            $adjId = '&adjId='.$txRefid;
            $order_id = $order->getId();
        }
        $billing = $order->getBillingAddress();
        if (empty($billing))
            $error = Mage::helper('paygate')->__('Invalid billing data.');

        $shipping = $order->getShippingAddress();
        // if(strlen($shipping->getFirstname()) <1)
        // if($order->getQuote()->isVirtual())
        // $shipping = $billing;

        if ($error !== false)     Mage::throwException($error);

        $post_vars = 'RequestType=BACKEND' .
            '&merchant_id='. $this->getAccount() .
            '&trnOrderNumber=' .$order_id .
            '&trnType=' . $requestType .
            $username .
            $password .
            $adjId .
            '&errorPage=' . "error" .
            '&approvedPage=' .'approved' ;

        if($requestType == 'P' || $requestType == 'PA')
        {
            $post_vars .=


                '&trnCardOwner='.$billing->getFirstname() . ' ' .$billing->getLastname() .
                    '&trnCardNumber=' .$payment->getCcNumber() .
                    '&trnExpMonth=' .sprintf('%02d',  $payment->getCcExpMonth()) .
                    '&trnExpYear=' .substr(sprintf('%04d',  $payment->getCcExpYear()), -2) .
                    '&trnCardCvd=' .$payment->getCcCid() ;
        }

        $post_vars .=   '&ordName=' .$billing->getFirstname() . ' ' .$billing->getLastname() .
            '&ordEmailAddress=' .$order->getCustomerEmail() .
            '&ordPhoneNumber=' .$billing->getTelephone().
            '&ordAddress1=' .$billing->getStreet(1) .
            '&ordCity=' .$billing->getCity();

        $countryid=0;
        $countryid=$billing->getCountry();
        $state_canus=$billing->getRegionCode();
        $state_zone=$billing->getRegionCode();
        if (($countryid == 'CA' || $countryid == 'US' ) && $state_canus) {
            $post_vars .=  '&ordProvince=' .$state_canus;
        } else {
            $post_vars .=  '&ordProvince=' ."--";
        }

        $post_vars .=  '&ordPostalCode=' .$billing->getPostcode() .
            '&ordCountry=' .$billing->getCountry();

        if(!empty($shipping)) {
            $post_vars .=  '&shipName=' .$shipping->getFirstname() . ' ' .$shipping->getLastname() .
                '&shipAddress1=' .$shipping->getStreet(1) .
                '&shipCity=' .$shipping->getCity() ;

            // beanstream uses state only for canada/us.
            $country_delivery_id=null;
            $country_delivery_id=$shipping->getCountry();
            $state_delivery_canus=$shipping->getRegionCode();
            if (($country_delivery_id == 'CA' || $country_delivery_id == 'US' ) && $state_delivery_canus) {
                $post_vars .= '&shipProvince=' .$shipping->getRegionCode();
            } else {
                $post_vars .= '&shipProvince=' ."--";
            }

            $country_delivery_abbrev=$shipping->getCountry();
            $post_vars .= '&shipPostalCode=' .$shipping->getPostcode() .
                '&shipCountry=' .$country_delivery_abbrev;

        }

        $post_vars .=			'&ordItemPrice=' .'0.00' .
            '&ordShippingPrice=' .'0.00' .
            '&ordTax1Price=' .'0.00' .
            '&trnAmount=' .sprintf("%01.2f",$this->_amount);


        return $post_vars;
    }

    protected function _postRequest($txnMode, $txnRequest)
    {

        $url = "https://www.beanstream.com/scripts/process_transaction.asp";


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$txnRequest);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $qresult = curl_exec($ch);
        $result = array();


        foreach (explode("&", $qresult) as $value) {
            $aryElement = explode("=", $value);
            eval('$result["' . $aryElement[0] . '"] = "' . $aryElement[1] . '";');
        }

        if($this->getTest())
        {
            Mage::log(__CLASS__ . __FUNCTION__ . " Sent: ". $txnMode."..".$txnRequest."\n");
            Mage::log(" Received: ". print_r($qresult,true)."\n");
        }

        if (curl_errno($ch)) {
            Mage::log(__CLASS__ . " Problem with curl and beanstream");
            $result['description'] .= ' <br />There was an error with our system, pleas try later';
        } else {
            curl_close($ch);

            $trnId = $result['trnId'];
            $trnOrderNumber = $result['trnOrderNumber'];
            $trnAmount = urldecode($result['trnAmount']);
            $authCode = $result['authCode'];
            $messageText = $result['messageText'];
            $trnDate = urldecode($result['trnDate']);

            $comment = "
			-Transaction Id: ".(strlen($trnId) !== false ? $trnId : '')."
			-Order Number: ".(strlen($trnOrderNumber) !== false ? $trnOrderNumber : '')."
			-Amount: ".(strlen($trnAmount) !== false ? $trnAmount : '')."
			-Currency Type: ". $this->_payment->getOrder()->getOrderCurrency()->getCurrencyCode()."
			-Auth Code: ".(strlen($authCode) !== false ? $authCode : '')."
			-Response message text: ".(strlen($messageText) !== false ? $messageText : '')."
			-Transaction Date: ".(strlen($trnDate) !== false ? $trnDate : '')."
";




            Mage::getSingleton('customer/session')->setBeanstreamData($comment);



            if ($result['trnApproved'] == '1')
            {
                $result['status'] = 'ACCEPTED';
            }
            else
            {
                $result['status'] = 'DECLINED';
                $result['description'] = $result['messageText'];
            }
        }

        return $result;
    }

    private function _dbinsert ($tablename,  $dataKey, $order, $last_four, $expiry_date, $postdata, $data=array() )
    {
        return $this->_real_dbinsert ($tablename,  $dataKey, $this->_payment->_data['cc_type'], $order->_data['customer_id'], $last_four, $expiry_date, $postdata, $data );
    }

    private function _real_dbinsert ($tablename,  $dataKey, $cc_type, $customer_id, $last_four, $expiry_date, $postdata, $data=array() )
    {
        mage::log(__METHOD__ . __LINE__ . "	data:  " . print_r($data['payment']['cc_owner'], 1), null, "ch_cc_name.log");
        try {
            $cardtype = $cc_type;
            $cid = $customer_id;
            $dkey = $dataKey;
			$dob = isset($postdata['ref1']) ?  $postdata['ref1'] : '';
            $insertsql = "INSERT INTO {$tablename} SET
	data_key =  '{$dkey}',
	 create_at =  now(),
	 update_at =  now(),
	 payment_type =  'NA',
	 store_id =  'NA',
	 customer_id =  '{$cid}',
	 customer_name =  '{$data['payment']['cc_owner']}',
	 card_expiry_MMYY = '{$expiry_date}' ,
	 cc_last4 =  '{$last_four}',
	 trnOrderNumber =  '{$postdata['trnOrderNumber']}',
	 dob =  '{$dob}',
	 cardtype =  '{$cardtype}'		";
            $magdbwrite = Mage::getSingleton('core/resource')->getConnection('core_write');
            //mage::log (__FILE__.':'.__LINE__.': $insertsql=' . $insertsql);
            $magdbwrite->query ($insertsql);
        } catch (Exception $e) {
            mage::log(__FILE__ . " 			we have excception " .$e->getMessage());
            return;
        }
    }


}
