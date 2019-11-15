<?php

class Collinsharper_Beanpro_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{

    protected $_code = 'beanpro';
    protected $_parentcode = 'beanpro';
    protected $_token_code = 'beanprostored';
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
    protected $_payment_type            = false;
    protected $_cc_last_four            = false;
    protected $_cc_type                 = false;
    protected $_payment;
    protected $_canManageRecurringProfiles  = true;


    protected $_formBlockType = 'beanpro/form_beanpro';
    protected $_infoBlockType = 'beanpro/info_beanpro';
    const PENDINGVBV = 'pending_beanstreamprofilevbv';
    const SERVICEVERSION = 1.2;
    const BEANSTREAMTOKENLENGTH = 32;
    const ACTION_PURCHASE = 'P';
    const ACTION_AUTH = 'PA';
    const ACTION_AUTH_CAP = 'PAC';
    const AUTHORIZE = 'authorize';
    const ZERO_AUTHORIZE = 'zero_authorize';
    const ZERO_ID = 'ZERO-0000000';


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

    public function getTokenCode()
    {
        return $this->_token_code;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function isAdmin()
    {
        if(Mage::app()->getStore()->isAdmin()) {
            return true;
        }

        if(Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }

        return false;
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
        if($this->overMaxCheckoutTotal()) {
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
        $path = 'payment/'.$this->_code.'/'.$v;
        //return Mage::getStoreConfig();
        // First check to see if we are in Admin or not
        $value = Mage::getStoreConfig($path);
        $storeId = false;

        if ($this->isAdmin()) {
            // Are we in credit memo?
            $_creditMemo = Mage::registry('current_creditmemo');
            if ($_creditMemo) {
                $storeId = $_creditMemo->getOrder()->getData("store_id");
            } else {
                $session = Mage::getSingleton('adminhtml/session_quote');
                // We will get the store ID from here
                $storeId = $session->getStoreId();
            }

            if(!$storeId) {
                if(Mage::registry('current_order') &&  Mage::registry('current_order')->getStoreId()) {
                    $storeId = Mage::registry('current_order')->getStoreId();
                } else if ( Mage::registry('current_invoice') &&  Mage::registry('current_invoice')->getStoreId()) {
                    $storeId = Mage::registry('current_invoice')->getStoreId();
                }
            }


            if($storeId) {
                $value = Mage::getStoreConfig($path, Mage::getModel('core/store')->load( $storeId ));
            }
        }
        return $value;

    }

    public function getParentConfig($v)
    {
      //  return Mage::getStoreConfig('payment/'.$this->_parentcode.'/'.$v);
        $path = 'payment/'.$this->_parentcode.'/'.$v;
        //return Mage::getStoreConfig();
        // First check to see if we are in Admin or not
        $value = Mage::getStoreConfig($path);
        $storeId = false;

        if ($this->isAdmin()) {
            // Are we in credit memo?
            $_creditMemo = Mage::registry('current_creditmemo');
            if ($_creditMemo) {
                $storeId = $_creditMemo->getOrder()->getData("store_id");
            } else {
                $session = Mage::getSingleton('adminhtml/session_quote');
                // We will get the store ID from here
                $storeId = $session->getStoreId();
            }

            if(!$storeId) {
                if(Mage::registry('current_order') &&  Mage::registry('current_order')->getStoreId()) {
                    $storeId = Mage::registry('current_order')->getStoreId();
                } else if ( Mage::registry('current_invoice') &&  Mage::registry('current_invoice')->getStoreId()) {
                    $storeId = Mage::registry('current_invoice')->getStoreId();
                }
            }


            if($storeId) {
                $value = Mage::getStoreConfig($path, Mage::getModel('core/store')->load( $storeId ));
            }
        }
        return $value;
    }

    protected function help()
    {
        return Mage::helper('beanpro');
    }

    protected function _getHelper()
    {
        return $this->help();
    }

    protected function log($txt)
    {
        if($this->getDebug() || $this->getTest()) {
            Mage:: log($txt);
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

    public function getPaymentAction()
    {
        return $this->getParentConfig('payment_action');
    }

    public function _isLegatoToken()
    {
        return $this->getConfig('legato_active') &&
        strstr($this->_payment->getCcOwner(), 'LEGATO-') !== false;
    }

    public function getPayment()
    {
        return $this->_payment;
    }

    public function generateUniqueOrderId($len = 30)
    {
        $order = $this->getPayment()->getOrder();

        if (!$order) {
            return '';
        }

        $incrementId = $order->getIncrementId();

        $tail = '';
        $len = $len - strlen($incrementId) - 1 ; // 1 for hyphen
        for ($i = 0; $i < $len; $i++) {
            $tail .= rand(0, 9);
        }

        return "{$incrementId}-{$tail}";
    }

    public function _preAuthCapture($payment, $txnMode, $transactionReferenceId)
    {
        $error = false;
        // TODO : shouldnt we go find the order id we used last time?

        $txnRequest = $this->_buildRequest($payment, $txnMode, $transactionReferenceId);
        $request = $this->_postRequest($txnMode, $txnRequest);

        if($request['trnApproved'] == 1) {

            $payment->setStatus('APPROVED');
            $payment->setLastTransId($request['trnId']);

        } else {
            $error = $this->_getFailureMessage($request);
        }

        if ($error !== false) {
            Mage::throwException(utf8_encode ($error));
        }

        return $this;
    }

    public function _getFailureMessage($request)
    {

        $error = Mage::helper('beanpro')->__('Error in capturing the payment (%s)', isset($request['description']) ? $request['description'] : '');
        if(isset($request['description'])) {

            $error = Mage::helper('beanpro')->__($request['description']);

        } else {

            $error = Mage::helper('beanpro')->__('Error in capturing the payment (%s)',$request['description']);
        }

        mage::log(__METHOD__ . __LINE__ . " we have " . $this->getParentConfig('forced_decline_message'));

        if($this->getParentConfig('forced_decline_message') != '') {
            $error = Mage::helper('beanpro')->__($this->getParentConfig('forced_decline_message'));
        }

        return $error;
    }

    public function _processTransaction()
    {
        $PaymentData = new Varien_Object;
        // decide if its capture or PAC
        $payment = $this->_payment;
        $payment = $this->_payment;
        $amount =  $this->_amount;

        $order_id = $this->generateUniqueOrderId();
        $this->getCheckoutSession()->setBeanstreamRedirect(false);
        $PaymentData->txRefid = false;
        $PaymentData->isZero = false;

        // NOTE - if we are in zero auth mode and setCcTransId == zero then we force to
        mage::log(__METHOD__ . __LINE__ . " wehave an issue " . $payment->getCcTransId()  ." and " .  self::ZERO_ID);
        if ($this->getParentConfig('zero_dollar_auth') &&
            $this->_payment_type == 'capture' &&
            $payment->getCcTransId() == self::ZERO_ID) {
            $this->_payment_type = self::ACTION_PURCHASE;
            $PaymentData->txnMode = self::ACTION_PURCHASE;
            $PaymentData->isZero = true;
            // remove zero id
            $payment->setCcTransId();
        }

        switch ($this->_payment_type) {
            case self::AUTHORIZE: {
                $PaymentData->txnMode =  self::ACTION_AUTH;
            }
                break;
            case self::ZERO_AUTHORIZE: {
                $PaymentData->txnMode =  self::ACTION_AUTH;
                $this->_amount = 0.00;
            }
                break;
            default: {
                $PaymentData->txnMode = self::ACTION_PURCHASE;
                if ($payment->getCcTransId() && $this->getParentConfig('payment_action') == self::AUTHORIZE) {
                    $PaymentData->txRefid = $payment->getCcTransId();
                    $PaymentData->txnMode = self::ACTION_AUTH_CAP;
                    return $this->_preAuthCapture($payment, self::ACTION_AUTH_CAP, $payment->getCcTransId());

                }

                if (ceil($amount) <= 0) {
                    $this->_amount = $amount = $this->getQuoteTotal();
                    $this->log(__METHOD__ . __LINE__ . "FORCING AMOUNT TO QUOTE" . $amount);
                    if (ceil($amount) <= 0) {
                        $error = $this->help()->__('Invalid amount for capture.');
                    }
                }
            }
                break;
        }

        $PaymentData->amount = $this->_amount;

        if($this->getDebug() || $this->getTest()) {
            $this->log(__METHOD__ . "in {$this->_payment_type} " . $payment->getCcTransId() . " and " . $payment->getLastTransId());
            $this->log(__METHOD__ . "in {$this->_payment_type} with  " . $amount);
            $this->log(__METHOD__ . " CCowner " . $payment->getCcOwner());
            $this->log(__METHOD__ . __LINE__ . " quote ttl " . $this->getQuoteTotal());

        }

        if ($this->_payment_type != self::ZERO_AUTHORIZE) {
            if($amount<0) {
                $error = $this->help()->__('Invalid amount for ' . $this->_payment_type);
                Mage::throwException($error);
            }
        }

        $order = $payment->getOrder();


        $billing = $order->getBillingAddress();

        if (empty($billing)) {
            $error =  $this->help()->__('Invalid billing data.');
            Mage::throwException($error);
        }

        // if we are parent we create account - if we are olverlaoded we use CCowner
        if ((!$this->_isAdminHasProfileForceToken() && $this->_code == $this->_parentcode)) {

            $this->log(__METHOD__ . __LINE__);
            $postdata = $this->_buildPost($payment, $order, $order_id);
            if($this->_islegatoToken()) {
                $tokenParts = explode("-", $payment->getCcOwner());
                array_shift($tokenParts);
                $this->_ccType = array_shift($tokenParts);
                $this->_cc_last_four = array_pop($tokenParts);

                $postdata['singleUseToken'] = implode("-",$tokenParts);
                // TODO if test mode skip card validation.
                unset($postdata['trnExpMonth']);
                unset($postdata['trnExpYear']);
                unset($postdata['cardValidation']);

                $this->log(__METHOD__ . " LEGATO token retrieval " . $this->_customerCode );
                unset($postdata['trnCardNumber']);

                unset($postdata['trnCardCvd']);
                $this->log(__METHOD__ . __LINE__ . 'LEGATO ' . print_r($postdata, 1));

                // TODO: iss this right?
                $payment->unsCcOwner();
            }

            $request = $this->_createAccount($payment, $postdata);


            // switch back to the token now.. ??
//            if($this->_islegatoToken()) {
//                $payment->setCcOwner($this->_customerCode);
//            }
//
            if($request !== true) {

                if($this->getConfig('forced_decline_message') != '') {
                    $request = Mage::helper('beanpro')->__($this->getConfig('forced_decline_message'));
                }

                Mage::throwException($this->_getHelper()->__($request));
            }

            if($this->getDebug() || $this->getTest()) {
                $this->log(__METHOD__ . " returned data " . print_r($request,1));
            }

        } else {

            $this->_customerCode = $payment->getCcOwner();

        }

        // we dont actually do anything here.. just get a token - save it and walk away
        if($this->_payment_type == self::ZERO_AUTHORIZE) {

                        $payment->setStatus('APPROVED');

            if (!$payment->getCcTransId()) {
                $payment->setCcTransId(self::ZERO_ID);
            }

            $payment->setLastTransId(self::ZERO_ID);
            $payment
                //->setCcTransId($return['trnId'])
                ->setCcApproval(self::ZERO_ID)
                ->setCcAvsStatus(self::ZERO_ID)
                ->setCcCidStatus(self::ZERO_ID)
                ->setCcOwner($this->_customerCode)
            ;
            $this->log(__METHOD__ . __LINE__ );
            $this->_payment = false;

            $order = $payment->getOrder();
            
            $order->setState(($this->getConfig('order_status') == Mage_Sales_Model_Order::STATE_PROCESSING) ? Mage_Sales_Model_Order::STATE_PROCESSING : Mage_Sales_Model_Order::STATE_COMPLETE, true);
            
            return $this;
        }

        $return = $this->_processStoredPurchase($payment, $this->_customerCode, $order_id, $PaymentData);
        Mage::getSingleton('core/session')->setChProfileToken($this->_customerCode);
        Mage::getSingleton('core/session')->setChProfileModel($this->getTokenCode());

        $payment->setCcOwner($this->_customerCode);

        if($this->getDebug() || $this->getTest()) {
            $this->log(__METHOD__ . __LINE__ . " returned data from process tored card " . print_r($return,1));
        }


        if($return['responseType'] == 'R') {
            if($this->getDebug() || $this->getTest()) {
                $this->log(__METHOD__ . __LINE__ ." we have qid" . $payment->getOrder()->getQuoteId());
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
        } elseif ($return['trnApproved'] == 1) {
            $payment->setStatus('APPROVED');

            if (!$payment->getCcTransId()) {
                $payment->setCcTransId($return['trnId']);
            }

            $payment->setLastTransId($return['trnId']);
            $payment
                //->setCcTransId($return['trnId'])
                ->setCcApproval($return['authCode'])
                ->setCcAvsStatus($return['avsMessage'])
                ->setCcCidStatus($return['cvdId']);
            $this->log(__METHOD__ . __LINE__ );
            $this->_payment = false;

            $order = $payment->getOrder();
            
            $order->setState(($this->getConfig('order_status') == Mage_Sales_Model_Order::STATE_PROCESSING) ? Mage_Sales_Model_Order::STATE_PROCESSING : Mage_Sales_Model_Order::STATE_COMPLETE, true);

            if (isset($return['capture_return'])) {
                // we only set this if we dont have it?
                if (!$payment->getCcTransId()) {
                    $payment->setCcTransId($return['capture_return']['trnId']);
                    $payment->setCcTransId($return['capture_return']['trnId']);
                }

                $payment->setLastTransId($return['capture_return']['trnId'])
                    ->setCcAvsStatus($return['capture_return']['avsMessage'])
                    ->setCcOwner($this->_customerCode);
            }


            return $this;
        }

        $this->_mailFailure($return);
        $return_message = $this->_makeReturnMessage($return);

        if($this->getConfig('forced_decline_message') != '') {
            $request = Mage::helper('beanpro')->__($this->getConfig('forced_decline_message'));
        }


        throw new Exception($return_message);
        Mage::throwException($return_message);
        //Mage::throwException($return_message);

    }

    public function __NOT_USED_YET_processLegatoToken()
    {
        $billing = $this->_payment->getOrder()->getBillingAddress();
        $billingName = $billing->getFirstname() . ' ' . $billing->getLastname();
        $email = $this->_payment->getOrder()->getCustomerEmail();
        if(!$email) {
            $email = $billing->getEmail();
        }

        $data = array(
            'payment_method' => 'token',
            'order_number' => '',
            'amount' => $this->_amount,
            'language' => 'eng',
            'customer_ip' => Mage::helper('core/http')->getRemoteAddr(),
        );

        $data['token'] = array(
            'complete' => ($this->_payment_type == 'authorize') ? false : true,
            'code' => $this->_customerCode,
            'name' => $billingName,
        );



        $data['billing'] = array(
            'name' => $billingName,
            'address_line1' => $billing->getStreet(1),
            'city' => $billing->getCity(),
            'province' => ((($billing->getCountry() == 'CA' || $billing->getCountry() == 'US' ) && $billing->getRegionCode()) ? $billing->getRegionCode() : "--"),
            'country' => $billing->getCountry(),
            'postal_code' => $billing->getPostcode(),
            'phone_number' => $billing->getTelephone(),
            'email_address' => $email,
        );

        if($this->_payment->getOrder()->getShippingAddress()) {
            $shipping = $this->_payment->getOrder()->getShippingAddress();
            $data['shipping'] = array(
                'name' => $shipping->getFirstname() . ' ' . $shipping->getLastname(),
                'address_line1' => $shipping->getStreet(1),
                'city' => $shipping->getCity(),
                'province' => ((($shipping->getCountry() == 'CA' || $shipping->getCountry() == 'US' ) && $shipping->getRegionCode()) ? $shipping->getRegionCode() : "--"),
                'country' => $shipping->getCountry(),
                'postal_code' => $shipping->getPostcode(),
            );
        }


        $url = false . " we dont use this yet";
        $ch = curl_init();

        // CURL the url with it
        /// https://www.beanstream.com/api/v1/payments
        curl_setopt($ch, CURLOPT_POST,1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($postdata));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $qresult = curl_exec($ch);
        parse_str($qresult,$return);

        if($this->getDebug() || $this->getTest()) {
            $this->log(__METHOD__ . __LINE__ . print_r($qresult,1));
            $this->log(__METHOD__ . __LINE__ . print_r($return,1));
        }

    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_amount = $amount;
        $this->_payment = $payment;
        $this->_payment_type = 'authorize';

        if ($this->getParentConfig('zero_dollar_auth')) {
            $this->_payment_type = self::ZERO_AUTHORIZE;
        }

        return $this->_processTransaction();
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $this->_amount = $amount;
        $this->_payment = $payment;
        $this->_payment_type = 'capture';
        return $this->_processTransaction();
    }


    public function _buildPost($payment, $order, $order_id)
    {
        $billing = $order->getBillingAddress();

        if($order->getCustomerEmail() != "") {
            $email = $order->getCustomerEmail();
        } else {
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

        if($this->getConfig('cav_enabled')) {
            $_post_payment = Mage::app()->getRequest()->getParam('payment');
            $postdata['cavEnabled'] = 1;
            $postdata['cavServiceVersion'] = self::SERVICEVERSION;


            if($this->getConfig('cav_dob')) {
                $dob = isset($_post_payment['beanpro']) && isset($_post_payment['beanpro']['cc_dob']) ? $_post_payment['beanpro']['cc_dob'] : false;
                $dob_parts = explode("/",$dob);
                if(!$dob || !stristr($dob,"/") || count($dob_parts) != 3) {
                    $error = $this->help()->__('Invalid Date of Birth.');
                    Mage::throwException($error);
                }
                // ensure you dont transpose the MM and DD
                $postdata['cavBirthMonth'] = $dob_parts[0];
                $postdata['cavBirthDay'] = $dob_parts[1];
                $postdata['cavBirthYear'] = $dob_parts[2];
                $dob_parts = $dob = false;
            }

            if($this->getConfig('cav_sin')) {
                $sin = isset($_post_payment['beanpro']) && isset($_post_payment['beanpro']['cc_sin']) ? str_replace('-','',$_post_payment['beanpro']['cc_sin']) : false;
                if(!$sin || strlen($sin) != 9)
                {
                    $error =  $this->help()->__('Invalid SIN number.');
                    Mage::throwException($error);
                }
                $postdata['cavSIN'] = $sin;
                $sin = false;
            }

        }

        return $postdata;
    }

    public function _makeReturnMessage($data)
    {
        $help = mage::helper('beanpro/messages');

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

        foreach($available_messages as $messageKey => $accessor) {

            if(isset($data[$messageKey]) && $help->$accessor($data[$messageKey])) {

                $this->log(__METHOD__ .__LINE__ . "adding ". $help->$accessor($data[$messageKey]));
                $return .= "<br />".$help->$accessor($data[$messageKey]);
            }

        }

        $this->log(__METHOD__ .__LINE__ . " returning ". $return);
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

            $this->log(__METHOD__ . " sending fail mail ". print_r($return,1));
            // we dont need to send these in debug
            //$return->ref1 == '';
            if(is_object($return))
            {
                $return->ref2 = 'removed for security';
            }

            $this->log(__METHOD__ . " sending fail mail ". print_r($return,1));

            foreach($mail_to as $m)
            {
                mail($m,'failure in beanstream payment','failure in beanstream payment'. print_r($return,1));
            }

        } catch (Exception $e) { $this->logException($e); }

    }

    private function getTermUrl()
    {
        return Mage::getUrl('beanpro/payment/return', array('_secure' => true));
    }

    //public function getCheckoutRedirectUrl()
    public function getOrderPlaceRedirectUrl()
    {
        //$this->log(__METHOD__ . "redirect bits " . print_r($this->getCheckoutSession()->getBeanstreamRedirect(),1));
        if($this->getCheckoutSession()->getBeanstreamRedirect() &&
            $this->getCheckoutSession()->getBeanstreamRedirect() !== false &&
            $this->getCheckoutSession()->getBeanstreamRedirect() != '') {
            return Mage::getUrl('beanpro/payment/redirect', array('_secure' => true));
        }
        return false;
    }


    public function _processStoredPurchase($payment, $customerCode, $orderId, $PaymentData)
    {
        // load Bday from the DB
        $_apostdata = array();

        if($this->getConfig('cav_dob') || $this->getConfig('cav_sin')) {
            $_apostdata['cavEnabled'] = 1;
            $_apostdata['cavServiceVersion'] = self::SERVICEVERSION;
        }

        if($this->getConfig('cav_dob')) {
            $_apostdata['cavBirthYear'] = '0000';
            $_apostdata['cavBirthMonth'] = '00';
            $_apostdata['cavBirthDay'] = '00';

            $dob = $this->help()->getTokenData($customerCode);
            if(isset($dob['dob']) || $dob['dob'] != '') {
                $dob_parts = explode("-",$dob['dob']);
                if(count($dob_parts) == 3) {
                    $_apostdata['cavBirthYear'] = $dob_parts[0];
                    $_apostdata['cavBirthMonth'] = $dob_parts[1];
                    $_apostdata['cavBirthDay'] = $dob_parts[2];
                }
            }
        }

        if($this->getConfig('cav_sin')) {
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

        if($PaymentData->isZero || $this->getParentConfig('purchase_action')) {
            $mode = 'P';
        }

        $postdata = array(
            'merchant_id' => $this->getAccount(),
            'requestType' => 'BACKEND',
            'trnOrderNumber' => $orderId,
            'trnAmount' => $PaymentData->amount,
            'errorPage' => 'na',
            'responseFormat' => 'XML',

            //$postData['customerIp'] = Mage::helper('core/http')->getRemoteAddr();
            'customerIp' => Mage::helper('core/http')->getRemoteAddr(),
            //'trnType' => $PaymentData->txnMode,
            'trnType' => $mode,
            'username' => $this->getParentConfig('login'),
            'password' => $this->getParentConfig('password'),
            'termUrl' => urlencode($this->getTermUrl()),
        );

        // we wouldnt have / use this ffor pre auth cap
        // we add in the CAV details here
        if($customerCode) {
            $postdata['customerCode'] = $customerCode; // profile id

            foreach($_apostdata as $k => $v) {
                $postdata[$k] = $v;
            }
        }

        if($PaymentData->txRefid) {
            $postdata['adjId'] = $PaymentData->txRefid;
        }
        // else
        // {
        // $postdata['customerCode'] = $customerCode;
        // } 
        // http_build_query killed the url?
        $_pdata = '';
        foreach($postdata as $k => $v) {
            $_d = "{$k}={$v}";
            if($k != 'termUrl') {
                $_d = str_replace("&","&amp;","{$k}={$v}");
            }

            $_pdata .= "&".$_d;
        }

        $_pdata = ltrim($_pdata,"&");

        if($this->getDebug() || $this->getTest()) {
            $this->log(__METHOD__ . __LINE__ . print_r( $postdata,1));
            $this->log(__METHOD__ . __LINE__ . print_r( $_pdata,1));
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

        if($this->getDebug() || $this->getTest()) {
            $this->log(__METHOD__ . __LINE__ . print_r($qresult,1));
            $this->log(__METHOD__ . __LINE__ . print_r($return,1));
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

        if($tmp != 1 || $PaymentData->txnMode == 'PAC') {
            return $return;
        }

        $this->log(__METHOD__ . __LINE__ . print_r($return,1));
        $order = $payment->getOrder();
        $cancel_transaction_return_failure = false;
        $_cvv_response = isset($return['cvdId']) ? $return['cvdId'] : false;
        $_avs_response = isset($return['avsId']) ? $return['avsId'] : false;
        $this->log(__METHOD__ . __LINE__ ."  CVV list " . $this->getParentConfig('cvv_approval_list') ." comparing " . $_cvv_response);
        $this->log(__METHOD__ . __LINE__ ."  AVS list " . $this->getParentConfig('avs_approval_list') . " comparing " .  $_avs_response);

        if($this->getParentConfig('cvv_approval_list') && $this->getParentConfig('cvv_approval_list') != '') {
            $this->log(__METHOD__ . __LINE__);
            $list = explode(",",$this->getParentConfig('cvv_approval_list'));
            if(!in_array($_cvv_response, $list)) {
                $this->log(__METHOD__ . __LINE__);
                $cancel_transaction_return_failure = 'cvv';
            }
        }

        if(!$cancel_transaction_return_failure &&
            $this->getParentConfig('avs_approval_list') &&
            $this->getParentConfig('avs_approval_list') != '') {
            $this->log(__METHOD__ . __LINE__);
            $list = explode(",",$this->getParentConfig('avs_approval_list'));
            if(!in_array($_avs_response, $list)) {
                $this->log(__METHOD__ . __LINE__);
                $cancel_transaction_return_failure = 'avs';
            }
        }

        if($cancel_transaction_return_failure !== false) {
            $this->log(__METHOD__ . __LINE__);
            $order->addStatusToHistory(
                $order->getStatus(), // keep order status/state
                $this->help()->__('Payment Failed ' .$cancel_transaction_return_failure, true),
                $notified = false
            );

            $this->log(__METHOD__ . __LINE__ . "Failing Transaction due to {$cancel_transaction_return_failure} failure");

            // add comment to the order
            // void the transaction
            $return['messageId'] = '99999999';
            $return['trnApproved'] = '99999999';
            $return['messageText'] = $this->help()->__('Merchant_Declined_'.$cancel_transaction_return_failure);
            if($cancel_transaction_return_failure == 'avs') {
                $return['messageText'] = $this->help()->__('Merchant_Declined_'.$return['avsMessage']);

            }


            try {


                if($this->getParentConfig('purchase_action')) {
                    // TODO delete the profile
                    /*
                     * DELETE_PROFILE Request Fields
Variable	Required/Optional	Type/Length	Description
serviceVersion	R	A/N	Must be set to 1.1
operationType	R	A/N	Must be “DELETE_PROFILE”
merchantId	R	N 9 digits	Merchant ID for account in Beanstream
passCode	O	Up to 32 A/N	Either this or hashValue must be passed, according to settings in Payment Profile Configuration Screen
hashValue	O	A/N (QS Only)	Either this or passCode must be passed, according to settings in Payment Profile Configuration Screen
customerCode	R	Up to 32 A/N	Must match existing customerCode
responseFormat	R	A/N	Will be XML for XML formatted response, QS for a querystring based response.

                     */
                    $voidPayment = new Varien_Object;
                    $voidPayment->setVoidTransactionId($return['trnId']);
                    $voidPayment->setOrder($payment->getOrder());
                    $vreturn = $this->void($voidPayment);

                    // TODO we should turn the profile here
                }

                $this->log(__METHOD__ . __LINE__);
                $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    $this->help()->__('Payment successfully voided due to AVS / CVV failure TxId: '.$vreturn['trnId'], true),
                    $notified = false
                );
                $return['void_data'] = $vreturn;
            } catch (Exception $e) {
                // if the void failed we have a problem
                $this->log(__METHOD__  . " Failed to void transaction after failed avs / cvv data  " .$e->getMessage());
                $return['internal_message'] = " Voiding the transaction after a failed AVS / CVV value did not succeed";
                $this->_mailFailure($return);
                $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    $this->help()->__('Failed to void payment - please manually check with the merchant the payment is canceled.', true),
                    $notified = false
                );
            }


        }

        // if we are not in purchase mode - we have to capture it if its successful
        if($PaymentData->txnMode != 'P' && $cancel_transaction_return_failure == false &&
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
            if($captureAuth['trnApproved'] != 1) {
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

            // run the capture on the transaction

        }

        return $return;
    }


    public function _createAccount($payment, $postdata)
    {

        // disable card verification..
        if($this->getParentConfig('disable_profile_card_verification')) {
            $this->log(__METHOD__  . " Disabling card verification on account create.." );
            $postdata['cardValidation'] = 0;
        }

        $order = $payment->getOrder();

        $url = "https://www.beanstream.com/scripts/payment_profile.asp";

        if(isset($postdata['cavSIN'])) {
            // Encrypt it.
            $postdata['ref2'] = Mage::helper('core')->encrypt($postdata['cavSIN']);
            unset($postdata['cavSIN']);

        }

        if(isset($postdata['cavBirthDay']) && isset($postdata['cavBirthMonth']) && isset($postdata['cavBirthYear'])) {
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

        if($this->getDebug() || $this->getTest()) {
            $this->log(__METHOD__ . " sent data " . print_r($postdata,1));
            $this->log(__METHOD__ . " returned data " . print_r($qresult,1));
        }

        $data = simplexml_load_string($qresult);
        $this->help()->recordTrxData($data);
        $postdata['trnOrderNumber'] = $data->cardVerification->trnOrderNumber;

        if(property_exists($data, 'responseCode') && $data->responseCode == 1) {
            //$this->log(__METHOD__ . "storing toking " );
            $card = substr($data->trnCardNumber,-4);
            $cc_exp = sprintf('%02d',  $payment->getCcExpMonth()). substr($payment->getCcExpYear(),-2);

            $lastFour = str_pad($card,4,"0");
            if($this->_cc_last_four) {
                $lastFour = $this->_cc_last_four;
                $payment->setCcLast4($lastFour);
                $payment->setCcType($this->_ccType);
                $this->_cc_last_four = false;
                $this->_ccType = false;
            }

            if(isset($order->_data['customer_id'])) {
                $this->_dbinsert ($this->help()->_getTable(), $data->customerCode,  $order, $lastFour, $cc_exp, $postdata );
            }
            $this->_customerCode = (string)$data->customerCode[0];
            return true;
        }


        $this->_mailFailure($data);
        $this->log(__METHOD__ . __LINE__ . print_r($data,1));
        $return = $data->responseMessage;
        if(is_object($data->cardVerification) && is_object($data->cardVerification->messageText)) {
            // KL: Fix for ticket 759 - Blank message popping up when duplicate submission happenned.
            if (strlen($data->cardVerification->messageId) > 0) {
                $return = $this->_getHelper()->__($data->cardVerification->messageText.' '.$data->cardVerification->messageId);
            }
        }

        return $return;
    }

    public function createToken($data)
    {
        // I dont think tthis function works any more
        if($this->getTest() || $this->getDebug()) {
            $this->log(__METHOD__ . "running" 	. __LINE__  . print_r($data,1)."\n");
        }

        $shipping = $billing = mage::getModel('customer/address')->load($data['billing_address_id']);
        $data['billing_address'] = $billing;
        $session = mage::getSingleton('customer/session');
        //$this->log(__METHOD__  . " customer id  " . $session->getCustomerId());
        //$this->log(__METHOD__  . " customer id  " . $billing->getCustomerId());

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
            $this->log(__METHOD__  . " we have mismatched customer data ");
        }

        $payment = new Varien_object;
        $order = new Varien_Object;

        $order_id = rand();
        $order->setCustomerEmail( $session->getCustomer()->getEmail());
        $order->setBillingAddress($billing);
        $order->setCustomerId($session->getCustomer()->getId());
        $payment->setCcNumber($data['payment']['cc_number']);
        $payment->setCcExpMonth($data['payment']['cc_exp_month']);
        $payment->setCcExpYear($data['payment']['cc_exp_year']);

        $payment->setCcType($data['payment']['cc_type']);

        $payment->setCcCid($data['payment']['cc_cid']);
        $order->setId($order_id);
        $payment->setOrder($order);
        $this->_payment = $payment;

        $postdata = $this->_buildPost($payment, $order, $order_id);

        $request = $this->_createAccount($payment, $postdata);
        //$this->log(__METHOD__ . " returning " . print_r($request,1));
        return $request;
    }

    public function authorizeDeposit($payment, $amount)
    {
        //		$session = Mage::getSingleton('checkout/session');
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $order = new Varien_Object;
        $order->setIncrementId(0);
        // TODO: this doesnt seem sane but we dont really use it

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
        $this->log("we have somem buits " . $payment->getStatus());
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
        //$this->log("TESTI?NG FOR CC TYPE " . print_r($availableTypes,1));
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

        if ($payment->getRefundTransactionId() && $amount>0) {
            $this->_payment = $payment;

            $txnRequest = $this->_buildRequest($payment,'R',$payment->getRefundTransactionId());
            $request = $this->_postRequest('R', $txnRequest);

            if ($request['trnApproved'] == 1) {
                $payment->setStatus('APPROVED');
            } else {
                $error = $request['description'];
            }

        } else {
            $error = $this->help()->__('Error in refunding the payment');
        }

        if ($error !== false) {
            Mage::throwException($error);
        }
        return $this;
    }

    public function void(Varien_Object $payment)
    {
        $this->log(__METHOD__ . __LINE__);
        $error = false;
        if($payment->getVoidTransactionId())
        {
            $this->log(__METHOD__ . __LINE__);
            $txmode = 'VP';
            $this->_payment = $payment;
            $txnRequest = $this->_buildRequest($payment, $txmode, $payment->getVoidTransactionId());
            $request = $this->_postRequest($txmode, $txnRequest);

            if($this->getDebug() || $this->getTest())
            {
                $this->log(__METHOD__ . " sent data " . print_r($txnRequest,1));
                $this->log(__METHOD__ . " returned data " . print_r($request,1));
            }

            if($request['trnApproved']==1){
                $payment->setStatus('APPROVED' );
            }
            else{
                $payment->setStatus(self::STATUS_ERROR);
                $error = $request['description'];
            }
        }else{
            $this->log(__METHOD__ . __LINE__);
            $payment->setStatus(self::STATUS_ERROR);
            $error = $this->help()->__('Invalid transaction id');
        }
        if ($error !== false) {
            $this->log(__METHOD__ . __LINE__);
            Mage::throwException($error);
        }
        return $this;
    }

    protected function _buildRequest(Varien_Object $payment, $requestType, $transactionReferenceId = 0)
    {

        $error = false;

        $postData = array();

        if($this->getParentConfig('login')) {
            $postData['username'] = $this->getParentConfig('login');
            $postData['password'] = $this->getParentConfig('password');
        }

        $order = $payment->getOrder();
        $order_id = $this->generateUniqueOrderId();

        if($transactionReferenceId != 0) {
            $postData['adjId'] = $transactionReferenceId;
            $order_id = $order->getId();
        }

        $billing = $order->getBillingAddress();

        if (empty($billing)) {
            $error = Mage::helper('paygate')->__('Invalid billing data.');

            if ($requestType != self::ACTION_AUTH_CAP && $error !== false) {
                Mage::throwException($error);
            }

        }

        $shipping = $order->getShippingAddress();

        if ($error !== false) {
            Mage::throwException($error);
        }

        $postData['RequestType'] = 'BACKEND';
        $postData['merchant_id'] = $this->getAccount();
        $postData['trnOrderNumber'] = $order_id;
        $postData['trnType'] = $requestType;
        $postData['errorPage'] = 'error';
        $postData['approvedPage'] = 'approved';

        if($requestType == 'P' || $requestType == 'PA') {

            $postData['trnCardOwner'] = $billing->getFirstname() . ' ' . $billing->getLastname();
            $postData['trnCardNumber'] = $payment->getCcNumber();
            $postData['trnExpMonth'] = sprintf('%02d',  $payment->getCcExpMonth());
            $postData['trnExpYear'] = substr(sprintf('%04d',  $payment->getCcExpYear()), -2);
            $postData['trnCardCvd'] = $payment->getCcCid();

        }

        $postData['ordName'] = $billing->getFirstname() . ' ' . $billing->getLastname();
        $postData['ordEmailAddress'] = $order->getCustomerEmail();
        $postData['ordPhoneNumber'] = $billing->getTelephone();
        $postData['ordAddress1'] = $billing->getStreet(1);
        $postData['ordCity'] = $billing->getCity();

        $countryid = $billing->getCountry();
        $stateProvCode = $billing->getRegionCode();
        $postData['ordProvince'] = '--';

        if (($countryid == 'CA' || $countryid == 'US' ) && $stateProvCode) {
            $postData['ordProvince'] = $stateProvCode;
        }

        $postData['ordPostalCode'] = $billing->getPostcode();
        $postData['ordCountry'] = $billing->getCountry();

        if(!empty($shipping)) {
            $postData['shipName'] = $shipping->getFirstname() . ' ' .$shipping->getLastname();
            $postData['shipAddress1'] = $shipping->getStreet(1);
            $postData['shipCity'] = $shipping->getCity();
            $postData['shipProvince'] = '--';

            $countryId = $shipping->getCountry();
            $stateProvCode = $shipping->getRegionCode();
            if (($countryId == 'CA' || $countryId == 'US' ) && $stateProvCode) {
                $postData['shipProvince'] = $stateProvCode;
            }

            $postData['shipPostalCode'] = $shipping->getPostcode();
            $postData['shipCountry'] = $shipping->getCountry();

        }


        $postData['ordItemPrice'] = '0.00';
        $postData['ordShippingPrice'] = '0.00';
        $postData['ordTax1Price'] = '0.00';
        $postData['trnAmount'] = sprintf("%01.2f",$this->_amount);

        //Send customer ip address
        $postData['customerIp'] = Mage::helper('core/http')->getRemoteAddr();


        return $postData;
    }

    protected function _postRequest($txnMode, $txnRequest)
    {

        $url = "https://www.beanstream.com/scripts/process_transaction.asp";


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($txnRequest));
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $qresult = curl_exec($ch);
        $result = array();


        // TODO parse this properly..
        foreach (explode("&", $qresult) as $value) {
            $aryElement = explode("=", $value);
            eval('$result["' . $aryElement[0] . '"] = "' . $aryElement[1] . '";');
        }

        if($this->getTest()) {
            $this->log(__METHOD__ . " Sent: $txnMode - " . print_r($txnRequest, 1));
            $this->log(__METHOD__ . " Sent: ". $txnMode."..");
            $this->log(__METHOD__ . " Received: ". print_r($qresult, true)."\n");
        }

        if (curl_errno($ch)) {
            $this->log(__METHOD__ . " Problem with curl and beanstream");
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

    private function _dbinsert ($tablename,  $dataKey, $order, $last_four, $expiry_date, $postdata )
    {
        return $this->_real_dbinsert ($tablename,  $dataKey, $this->_payment->_data['cc_type'], $order->_data['customer_id'], $last_four, $expiry_date, $postdata );
    }

    private function _real_dbinsert ($tablename,  $dataKey, $cc_type, $customer_id, $last_four, $expiry_date, $postdata )
    {
        // TODO replace this with an object
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
	 card_expiry_MMYY = '{$expiry_date}' ,
	 cc_last4 =  '{$last_four}',
	 trnOrderNumber =  '{$postdata['trnOrderNumber']}',
	 dob =  '{$dob}',
	 cardtype =  '{$cardtype}'		";
            $magdbwrite = Mage::getSingleton('core/resource')->getConnection('core_write');
            //$this->log (__FILE__.':'.__LINE__.': $insertsql=' . $insertsql);
            $magdbwrite->query ($insertsql);
        } catch (Exception $e) {
            $this->log(__FILE__ . " we have excception " .$e->getMessage());
            return;
        }
    }


    // RECURRING functions

    public function canManageRecurringProfiles()
    {
        return Mage::helper('core')->isModuleEnabled('Collinsharper_Recurring') &&
        $this->_canManageRecurringProfiles
        && ($this instanceof Mage_Payment_Model_Recurring_Profile_MethodInterface);
    }


    /**
     * TODO: what should this do?
     *  TODO: Abstract it to CH recurring.
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @return type
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {

    }

    /**
     * TODO: Abstract it to CH recurring.
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile, Mage_Payment_Model_Info $paymentInfo)
    {
        return mage::helper('chrecurring')->_submitRecurringProfile($profile, $paymentInfo, $this->getCode(), $this->getTokenCode(), $this);
    }

// TODO what does this do?
    /**
     *
     * @param type $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        return true;
    }


    /**
     *
     * @return type
     */
    public function canGetRecurringProfileDetails()
    {
        return true;
    }

// TODO what does this do?
    /**
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return true;
    }

// TODO what does this do?
    /**
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return true;
    }

}
