<?php
/**
 * OpenCommerce Commercial Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OpenCommerce Commercial Extension License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.delorumcommerce.com/license/commercial-extension
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@OpenCommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this package to newer
 * versions in the future. 
 *
 * @category   OpenCommerce
 * @package    OpenCommerce_CIMple
 * @copyright  Copyright (c) 2013 OpenCommerce Inc. LLC
 * @license    http://store.opencommercellc.com/commercial-license
 */
class TinyBrick_Authorizenetcim_Model_Authorizenetcimsoap extends Mage_Payment_Model_Method_Cc
{
	
	protected $_code = 'authorizenetcim';
	protected $_formBlockType = 'authorizenetcim/form_authorizenetcim';
	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canVoid = true;
	protected $_canRefund = true;
	protected $_payment = false;
	protected $_canUseCheckout = true;
	public $_storeId;
	
	/**
	 * This getPaymentAction() gets the payment type from Magento 
	 */
	
	public function getPaymentAction()
	{
		return Mage::getStoreConfig('payment/authorizenetcim/payment_action');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mage_Payment_Model_Method_Abstract::authorize()
	 * This overwrites the authorize function and calls the callApi function
	 * From here, it contacts authorize.net
	 * Mage::helper('authorizenetcim')->response($response) - checks the response to make sure it is valid
	 */
	public function authorize(Varien_Object $payment, $amount)
	{
		$customer = $payment->getOrder()->getCustomer();
		$this->_storeId = $customer['store_id'];
		
		if($customer){
			$type = 'authorize';
		}else{
			$type = 'authorizeandcaptureAIM';
		}
		$response = $this->callApi($payment, $amount, $type);
		
		// Checks to see if we can connect to Authorize.net
		Mage::helper('authorizenetcim')->response($response, array( 'amnt' => $amount, 'type' => $type));
		
		$directResponseFields = explode(",", $response->directResponse);
		$transactionId = $directResponseFields[6];
		
		$payment->setTransactionId($transactionId);
		$payment->setIsTransactionClosed(0);
		$payment->setCcTransId($transactionId);
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mage_Payment_Model_Method_Abstract::capture()
	 * Overwrites the capture function and calls the callApi function
	 * Contacts authorize.net
	 * Mage::helper('authorizenetcim')->response($response) - checks the response to make sure it is valid
	 * @param object $payment Payment object
	 * @param int $amount Amount to capture
	 * @param string $type This is either useCIM or useAIM 
	 */
	
	public function capture(Varien_Object $payment, $amount, $type = NULL)
	{
         Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");

		$order = $payment->getOrder();
		$this->_storeId = $order->getStoreId();
		
		if($this->getPaymentAction()=='authorize') {
			if ($type == NULL || $type == 'useCIM'){
				$response = $this->callApi($payment, $amount, 'capture');
                Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__  . " and " . print_r($response ,1));

				// Checks to see if we can connect to Authorize.net
                Mage::helper('authorizenetcim')->response($response, array( 'amnt' => $amount, 'type' => 'capture'));

                $directResponseFields = explode(",", $response->directResponse);
				$transactionId = $directResponseFields[6];
			}
			else if ($type == 'useAIM'){
				$response = $this->callApi($payment, $amount, 'captureAIM');
				
				// Checks to see if we can connect to Authorize.net
//				Mage::helper('authorizenetcim')->response($response);
                Mage::helper('authorizenetcim')->response($response, array( 'amnt' => $amount, 'type' => 'cap AIM'));


                $transactionId = $response->transactionResponse->transId;
			}
		}
		else {
			if ($type == NULL || $type == 'useCIM'){
                mage::log(__METHOD__ . __LINE__);
				$response = $this->callApi($payment, $amount, 'authorizeandcapture');

               

			//	Mage::helper('authorizenetcim')->response($response);
                Mage::helper('authorizenetcim')->response($response, array( 'amnt' => $amount, 'type' => 'authorizeandcapture '));


                $directResponseFields = explode(",", $response->directResponse);
				$transactionId = $directResponseFields[6];
			}
			else if ($type == 'useAIM'){
                mage::log(__METHOD__ . __LINE__);
				$response = $this->callApi($payment, $amount, 'authorizeandcaptureAIM');
				
				// Checks to see if we can connect to Authorize.net
				//Mage::helper('authorizenetcim')->response($response);
                Mage::helper('authorizenetcim')->response($response, array( 'amnt' => $amount, 'type' => 'authorizeandcapture AIM '));

				$transactionId = $response->transactionResponse->transId;
			}
		}
				
		$payment->setCcTransId($transactionId);  // probably shouldn't set.
		$payment->setTransactionId($transactionId);
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mage_Payment_Model_Method_Abstract::void()
	 * Voids the transaction
	 * @param object $payment Payment object to void
	 */
	
	public function void(Varien_Object $payment)
	{
		$response = $this->callApi($payment, NULL ,'void');
		
		Mage::helper('authorizenetcim')->result($response);
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mage_Payment_Model_Method_Abstract::refund()
	 * Refunds the payment based on the object/amount
	 * @param object $payment Payment object
	 * @param int $amount Total to void
	 */
	
	public function refund(Varien_Object $payment, $amount) {
		
		$tokenPaymentProfileId = $payment->getTokenPaymentProfileId();
		
		if($tokenPaymentProfileId != 0){
			$response = $this->callApi($payment, $amount, 'refund');
			
			Mage::helper('authorizenetcim')->result($response);
		
		}
		else{
			$response = $this->callApi($payment, $amount, 'refundAIM');
			
			Mage::helper('authorizenetcim')->result($response);
		
		}
		
		return $this;
	}
	
	/**
	 * callApi is the major piece in the puzzle
	 * @param object $payment Payment Object
	 * @param int $amount Amount to charge
	 * @param string $type either CIM or AIM
	 * @param int $ccSaveId Used to determine whether or not a profile exists for the customer
	 * @param int $tokenProfileId Checks if the payment profile already exists, if not, creates it
	 */
	
	//prepare information and call specific xml api
	public function callApi(Varien_Object $payment, $amount, $type){

         Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");

        $this->_payment = $payment;
		$order = $payment->getOrder();
		$invoiceNumber = substr($order->getIncrementId() . '-' . rand(3,9999),0,20);
		if($type != 'authorizeandcaptureAIM'){
             Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");

            $customerID = $order->getCustomerId();
			$customerEmail = $order->getCustomerEmail();
			$billingInfo = $order->getBillingAddress();
			$shippingInfo = $order->getShippingAddress();
			
			$ccType = $payment->getCcType();
			$ccNumber = $payment->getCcNumber();
			$ccExpDate = $payment->getCcExpYear() .'-'. str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
			$ccCCV = $payment->getCcCid();
			
			$tokenProfileId = $payment->getTokenProfileId();
			$tokenPaymentProfileId = $payment->getTokenPaymentProfileId();
			
			$postData = Mage::app()->getRequest()->getPost('payment', array());
            $ccSaveId = null;
			if(isset($postData['ccsave_id']) && $postData['ccsave_id']){
				$ccSaveId = $postData['ccsave_id'];
			}
			
			if($ccSaveId != null){
				$profileData = Mage::getModel('authorizenetcim/authorizenetcim')->load($ccSaveId)->getData();
				$tokenProfileId = $profileData['token_profile_id'];
				$tokenPaymentProfileId = $profileData['token_payment_profile_id'];
			}
			
			if(($tokenProfileId==0 && $tokenPaymentProfileId==0) &&
                ($type == 'authorize' || $type == 'capture' || $type == 'authorizeandcapture')) {

                /**
                 * if we have an order ID in the session under a failure bit. there is a chance we didnt get to save the profile ID.
                 * Lets try to use that one and bypass the duplicate trans errors?
                 * $session->setAuthNetCimProfileId($tokenProfileId);
                 * $session->setAuthNetCimTokenId($tokenPaymentProfileId);
                 * $session->setAuthNetCimCreatedOrderId($order->getIncrementId());
                 *
                 */

                $session = Mage::getSingleton('checkout/session');

                Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , " checking for saved data Sess " . $session->getAuthNetCimFailure() );
                Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , " checking for saved data oid " . $order->getIncrementId() );
                Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , " checking for saved data toke  " .  $session->getAuthNetCimTokenId() );
                Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , " checking for saved data prof   " . $session->getAuthNetCimProfileId());

                if($ccSaveId == null && $session->getAuthNetCimFailure() == $order->getIncrementId() &&
                    $session->getAuthNetCimTokenId()) {
 Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
                    $tokenProfileId = $session->getAuthNetCimProfileId();
                    $tokenPaymentProfileId = $session->getAuthNetCimTokenId();

                } else if($ccSaveId != null) {
                    Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
					$profileData = Mage::getModel('authorizenetcim/authorizenetcim')->load($ccSaveId)->getData();
					$tokenProfileId = $profileData['token_profile_id'];
					$tokenPaymentProfileId = $profileData['token_payment_profile_id'];
				} else {
                    Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
                    $profile = Mage::getModel('authorizenetcim/authorizenetcim');
					$profileCollection = $profile->getCollection()->addFieldToFilter('customer_id', $customerID);
					
					if (count($profileCollection) == 0) {
                        Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
						$responseXML = $this->createCustomerProfileRequest($customerID, $customerEmail, $billingInfo, $shippingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType);
                         Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
						$tokenProfileId = $responseXML->customerProfileId;
						$tokenPaymentProfileId = $responseXML->customerPaymentProfileIdList->numericString;
					}
					else{
                        Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
						$tokenProfileId = $profileCollection->getFirstItem()->getTokenProfileId();
						$tokenPaymentProfileId = $this->createCustomerPaymentProfileRequest($customerID, $tokenProfileId, $billingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType);
 					}
                    Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
                    $session->setAuthNetCimProfileId((string)$tokenProfileId);
                    $session->setAuthNetCimTokenId((string)$tokenPaymentProfileId);
                    $session->setAuthNetCimCreatedOrderId((string)$order->getIncrementId());

				}
			}
		}
		//call xml creation functions
         Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
		switch($type) {
			case 'authorize':
                 Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
				$payment->setTokenProfileId($tokenProfileId);
				$payment->setTokenPaymentProfileId($tokenPaymentProfileId);
				
				$response = $this->createAuthorize($amount, $tokenProfileId, $tokenPaymentProfileId, $invoiceNumber, $ccCCV);
				break;
			case 'capture':
				//get authorize transaction id for capture
				$authorizeTransactionId = $payment->getCcTransId();
                 Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
				$response = $this->createCapture($amount, $tokenProfileId, $tokenPaymentProfileId, $authorizeTransactionId);
				break;
			case 'authorizeandcapture':
                 Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
				$payment->setTokenProfileId($tokenProfileId);
				$payment->setTokenPaymentProfileId($tokenPaymentProfileId);
				
				$response = $this->createAuthorizeCapture($amount, $tokenProfileId, $tokenPaymentProfileId, $invoiceNumber, $ccCCV);
				break;
			case 'void':
				$refundTransactionId = $payment->getRefundTransactionId();
				$response = $this->createVoid($tokenProfileId, $tokenPaymentProfileId, $refundTransactionId);
				break;
			case 'refund':
				$refundTransactionId = $payment->getRefundTransactionId();
				$response = $this->createRefund($amount, $tokenProfileId, $tokenPaymentProfileId, $refundTransactionId);
				break;
			case 'authorizeandcaptureAIM':
                 Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
				$response = $this->createAuthorizeCaptureAIM($amount, $payment, $order);
				break;
			case 'refundAIM':
				$response = $this->createRefundAIM($amount, $payment, $order);
				break;
		}

        $this->_payment = false;

		return $response;
	}	
	
	/**
	 * This sends the data to authorize.net and creates the customer profile request
	 * 
	 * It then saves the data to the database
	 * 
	 * @param int $customerID
	 * @param string $customerEmail
	 * @param object $billingInfo
	 * @param object $shippingInfo
	 * @param int $ccNumber
	 * @param date $ccExpDate
	 * @param int $ccCCV
	 * @param string $ccType
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createCustomerProfileRequest($customerID, $customerEmail, $billingInfo, $shippingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType)
	{
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
        mage::log(__METHOD__ . __LINE__ );
        $x = array(
            'profile' => array(
                'merchantCustomerId' => $customerID,
                'email' => $customerEmail,
                'paymentProfiles' => array(
                    'billTo' => array(
                        'firstName' => $billingInfo['firstname'],
                        'lastName' => $billingInfo['lastname'],
                        'address' => $billingInfo['street'],
                        'city' => $billingInfo['city'],
                        'state' => $billingInfo['region'],
                        'zip' => $billingInfo['postcode'],
                        'phoneNumber' => $billingInfo['telephone']
                    ),
                    'payment' => array(
                        'creditCard' => array(
                            'cardNumber' => $ccNumber,
                            'expirationDate' => $ccExpDate,
                        ),
                    ),
                ),
                'shipToList' => array(
                    'firstName' => $billingInfo['firstname'],
                    'lastName' => $billingInfo['lastname'],
                    'address' => $billingInfo['street'],
                    'city' => $billingInfo['city'],
                    'state' => $billingInfo['region'],
                    'zip' => $billingInfo['postcode'],
                    'phoneNumber' => $billingInfo['telephone']
                ),
            ),
            'validationMode' => 'none'
        );

		$xml->createCustomerProfileRequest($x);

        // 20140120 - Collins Harper E00039 is an error that the magento customer ID is already tied to a profile
        // magento doesnt know the CIM PID . If we get it. try to parse it - delete the account and recreate it.

        if(!$xml->isSuccessful() && $xml->messages->message->code == 'E00039' &&
            strstr($xml->messages->message->text,'A duplicate record with ID') != false) {

            Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ . " work around for an E00039 error ");

            try {
                $string = (string)$xml->messages->message->text;
                preg_match('/A duplicate record with ID (\d+) already exists/',$string,$match);
                if(isset($match[1])) {

                    $nxml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');

                    $nxml->deleteCustomerProfileRequest(array('customerProfileId' => $match[1]));

                    $this->_clearCustomerProfiles($customerID, $match[1]);

                    $xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
                    $xml->createCustomerProfileRequest($x);
                }
            } catch (Exception $e) {
                // we just be quiet..
            }
        }

        // 20140120 - Collins Harper E00039


    //    mage::log(__METHOD__ . __LINE__ . " data " . print_r($x,1));
		Mage::helper('authorizenetcim')->result($xml);
        mage::log(__METHOD__ . __LINE__ );
		$customerProfileID = $xml->customerProfileId;
		$customerPaymentProfileID = $xml->customerPaymentProfileIdList->numericString;
		$customerShippingAddressID = $xml->customerShippingAddressIdList->numericString;
        mage::log(__METHOD__ . __LINE__ );
		if($customerID != 0){
			$profileUpload = Mage::getModel('authorizenetcim/authorizenetcim');
			$profileUpload->setCustomerID($customerID);
			$profileUpload->setCcType($ccType);
			$profileUpload->setCcLast4(substr($ccNumber, -4, 4));
			$profileUpload->setCcExpMonth(substr($ccExpDate, -2));
			$profileUpload->setCcExpYear(substr($ccExpDate, 0, -3));
			$profileUpload->setTokenProfileId($customerProfileID);
			$profileUpload->setTokenPaymentProfileId($customerPaymentProfileID);
			$profileUpload->setTokenShippingAddressId($customerShippingAddressID);
			$profileUpload->save();
			
			// Also need to save the data into the customer_entity table
			$profileCustomerEntity = Mage::getModel('customer/customer');
			$profileCustomerEntity->load($customerID);
			$profileCustomerEntity->setData('token_profile_id', $customerProfileID->__toString());
			$profileCustomerEntity->save();
		}
        mage::log(__METHOD__ . __LINE__ );
		return $xml;
	}



    public function _getCreateCustomerPaymentProfileRequest($customerID, $tokenProfileId, $billingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType)
    {
        return array(
            'customerProfileId' => $tokenProfileId,
            'paymentProfile' => array(
                'billTo' => array(
                    'firstName' => $billingInfo['firstname'],
                    'lastName' => $billingInfo['lastname'],
                    'address' => $billingInfo['street'],
                    'city' => $billingInfo['city'],
                    'state' => $billingInfo['region'],
                    'zip' => $billingInfo['postcode'],
                    'phoneNumber' => $billingInfo['telephone']
                ),
                'payment' => array(
                    'creditCard' => array(
                        'cardNumber' => $ccNumber,
                        'expirationDate' => $ccExpDate,
                    )
                )
            ),
            'validationMode' => 'none'
        );
    }

	/**
	 * 
	 * Creates the customer profile inside of authorize.net
	 * 
	 * @param int $customerID
	 * @param int $tokenProfileId
	 * @param object $billingInfo
	 * @param int $ccNumber
	 * @param date $ccExpDate
	 * @param int $ccCCV
	 * @param string $ccType
	 * @return int $customerPaymentProfileID
	 */
	
	public function createCustomerPaymentProfileRequest($customerID, $tokenProfileId, $billingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType)
	{
        Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , "  ");
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
		$response = $xml->createCustomerPaymentProfileRequest($this->_getCreateCustomerPaymentProfileRequest($customerID, $tokenProfileId, $billingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType));
        $responseXml = $xml->getResponseXml();

        // 20140120 - Collins Harper
        /*
        * if the customer got a E00040 - AuthNet is indicating the account doesn't exist.
        * lets delete their account and recreate it then try again?
        */

        $cid = (int)$this->_payment->getOrder()->getCustomerId();


      //  if($cid && !$xml->isSuccessful() && ($response->messages->message->code == 'E00040')) {
        if($cid && $responseXml->messages->message->code == 'E00040') {

            Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ . " work around for an E00040 error ");

            $response = $this->_recreateProfile($customerID, $tokenProfileId, $billingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType);

            $tokenProfileId = $response->customerProfileId;
            $customerPaymentProfileID = $response->customerPaymentProfileIdList->numericString;
            $xml = $response;

        } else if (property_exists($xml,'customerPaymentProfileId')) {

            $customerPaymentProfileID = $xml->customerPaymentProfileId;
        }


        Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ );
        // 20140120 - Collins Harper

        Mage::helper('authorizenetcim')->result($xml);
        // 20140120 - Collins Harper
        //$customerPaymentProfileID = $xml->customerPaymentProfileId;
        Mage::helper('authorizenetcim')->debugLog(__METHOD__ , __LINE__ , " TPID $tokenProfileId CPID $customerPaymentProfileID  ");

		
		$profileUpload = Mage::getModel('authorizenetcim/authorizenetcim');
		$profileUpload->setCustomerID($customerID);
		$profileUpload->setCcType($ccType);
		$profileUpload->setCcLast4(substr($ccNumber, -4, 4));
		$profileUpload->setCcExpMonth(substr($ccExpDate, -2));
		$profileUpload->setCcExpYear(substr($ccExpDate, 0, -3));
		$profileUpload->setTokenProfileId($tokenProfileId);
		$profileUpload->setTokenPaymentProfileId($customerPaymentProfileID);
		$profileUpload->save();
		
		return $customerPaymentProfileID;
		
	}

    public function _recreateProfile($customerID, $tokenProfileId, $billingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType)
    {
        $payment = $this->_payment;
        $order = $payment->getOrder();
        $customerEmail = $order->getCustomerEmail();
        $shippingInfo = $order->getShippingAddress();

        $ccType = $payment->getCcType();
        $ccNumber = $payment->getCcNumber();
        $ccExpDate = $payment->getCcExpYear() .'-'. str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
        $ccCCV = $payment->getCcCid();
        $billingInfo = $order->getBillingAddress();
        $cid = (int)$this->_payment->getOrder()->getCustomerId();


        $this->_clearCustomerProfiles($cid, $tokenProfileId);


        $session = Mage::getSingleton('checkout/session');
        $session->setAuthNetCimProfileId(false);
        $session->setAuthNetCimTokenId(false);
        $session->setAuthNetCimFailure(false);

        // create profile here and save.. call again
        Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__  . " create new $ccNumber ");
        $xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
        Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ );
        $response = $this->createCustomerProfileRequest($customerID, $customerEmail, $billingInfo, $shippingInfo, $ccNumber, $ccExpDate, $ccCCV, $ccType);
        Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ );
        /// if we pass that we should be good right?!

        $xml = $response;
        return $xml;
    }

	/**
	 * Creates the authorization inside of authorize.net
	 * 
	 * @param int $amount Amount of the authorization
	 * @param int $tokenProfileId Customer profile Id
	 * @param int $tokenPaymentProfileId Customer Payment Profile ID to use
	 * @param int $invoiceNumber Invoice number from Magento
	 * @param int $ccCCV Last 4 of the Credit Card
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */


    public function  _clearCustomerProfiles($cid, $tokenProfileId = false)
    {

        $tableName = Mage::getSingleton('core/resource')->getTableName('tinybrick_authorizenetcim_ccsave');
        $sql = " delete from {$tableName} where customer_id = {$cid} ";
        Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ . " work around for an E00040  $sql  ");
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $x = $write->query($sql);

        if($tokenProfileId) {
            $sql = " delete from {$tableName} where token_profile_id = {$tokenProfileId} ";
            Mage::helper('authorizenetcim')->debugLog(__METHOD__ . __LINE__ . " work around for an E00040  $sql  ");
            $x = $write->query($sql);
        }

    }

	public function createAuthorize($amount, $tokenProfileId, $tokenPaymentProfileId, $invoiceNumber, $ccCCV)
	{
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
		$xml->createCustomerProfileTransactionRequest(array(
				'transaction' => array(
						'profileTransAuthOnly' => array(
								'amount' => $amount,
								'customerProfileId' => $tokenProfileId,
								'customerPaymentProfileId' => $tokenPaymentProfileId,
								'order' => array(
										'invoiceNumber' => $invoiceNumber,
								),
						)
				),
		));		
			
		return $xml;
	}
	
	/**
	 * Creates the capture in authorize.net
	 * 
	 * @param int $amount Amount of the authorization
	 * @param int $tokenProfileId Customer profile Id
	 * @param int $tokenPaymentProfileId Customer Payment Profile ID to use
	 * @param int $invoiceNumber Invoice number from Magento
	 * @param int $ccCCV Last 4 of the Credit Card
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createCapture($amount, $tokenProfileId, $tokenPaymentProfileId, $authorizeTransactionId)
	{
		
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
		$xml->createCustomerProfileTransactionRequest(array(
				'transaction' => array(
						'profileTransPriorAuthCapture' => array(
								'amount' => $amount,
								'customerProfileId' => $tokenProfileId,
								'customerPaymentProfileId' => $tokenPaymentProfileId,
								'transId' => substr($authorizeTransactionId . '-' . rand(3,9999),0,20)
						)
				),
		));		
		
		return $xml;
	}
	
	/**
	 * 
	 * Authorizes and then captures inside authorize.net
	 * 
	 * @param int $amount Amount of the authorization
	 * @param int $tokenProfileId Customer profile Id
	 * @param int $tokenPaymentProfileId Customer Payment Profile ID to use
	 * @param int $invoiceNumber Invoice number from Magento
	 * @param int $ccCCV Last 4 of the Credit Card
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createAuthorizeCapture($amount, $tokenProfileId, $tokenPaymentProfileId, $invoiceNumber, $ccCCV)
	{
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
mage::log(__METHOD__ . __LINE__  . " and " . $invoiceNumber);
		$xml->createCustomerProfileTransactionRequest(array(
				'transaction' => array(
						'profileTransAuthCapture' => array(
								'amount' => $amount,
								'customerProfileId' => $tokenProfileId,
								'customerPaymentProfileId' => $tokenPaymentProfileId,
								//'customerShippingAddressId' => '12156448',
								'order' => array(
										'invoiceNumber' => substr($invoiceNumber . '-' . rand(3,9999),0,20),
								),
								//may have to add if statement, if pulled from profile, do not pass CCV info; if credit card number entered for first time, pass CCV  (CCV is not stored in CIM table for use)
								//'cardCode' => $ccCCV
						)
				),
		));		
		
		return $xml;
		
	}
	
	/**
	 * Voides the transaction in authorize.net 
	 * 
	 * @param int $tokenProfileId Customer profile ID
	 * @param int $tokenPaymentProfileId Customer payment profile ID 
	 * @param int $authorizeTransactionId Authorize.net transaction ID to void
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createVoid($tokenProfileId, $tokenPaymentProfileId, $authorizeTransactionId)
	{
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
		$xml->createCustomerProfileTransactionRequest(array(
				'transaction' => array(
						'profileTransVoid' => array(
								'customerProfileId' => $tokenProfileId,
								'customerPaymentProfileId' => $tokenPaymentProfileId,
								//'customerShippingAddressId' => '4907537',
								'transId' => $authorizeTransactionId
						)
				),
		));
		return $xml;
	}
	
	/**
	 * 
	 * 
	 * @param int $amount Amount of the authorization
	 * @param int $tokenProfileId Customer profile ID
	 * @param int $tokenPaymentProfileId Customer payment profile ID 
	 * @param int $authorizeTransactionId Authorize.net transaction ID to void
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createRefund($amount, $tokenProfileId, $tokenPaymentProfileId, $authorizeTransactionId)
	{
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
		$xml->createCustomerProfileTransactionRequest(array(
				'transaction' => array(
						'profileTransRefund' => array(
								'amount' => $amount,
								'customerProfileId' => $tokenProfileId,
								'customerPaymentProfileId' => $tokenPaymentProfileId,
								'transId' => $authorizeTransactionId
						)
				),
		));
		
		return $xml;
	}
	
	/**
	 *  This uses AIM instead of CIM if the customer chooses not to save the card
	 * @param int $amount Amount to charge
	 * @param int $payment
	 * @param int $order Order Id
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createAuthorizeCaptureAIM($amount, $payment, $order)
	{
		$billingInfo = $order->getBillingAddress();
		$ccExpDate = $payment->getCcExpYear() .'-'. str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);	
		
mage::log(__METHOD__ . __LINE__  . " and "  );

		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
		$xml->createTransactionRequest(array(
		        'transactionRequest' => array(
		            'transactionType' => 'authCaptureTransaction',
		            'amount' => $amount,
		            'payment' => array(
		                'creditCard' => array(
		                    'cardNumber' => $payment->getCcNumber(),
		                    'expirationDate' => $ccExpDate,
		                ),
		            ),
		            'order' => array(
		                'invoiceNumber' => substr($order->getIncrementId() . '-' . rand(3,9999),0,20),
		            ),
		            'customer' => array(
		               'id' => $order->getCustomerId(),
		               'email' => $order->getCustomerEmail(),
		            ),
		            'billTo' => array(
		            		'firstName' => $billingInfo['firstname'],
		            		'lastName' => $billingInfo['lastname'],
		            		'address' => $billingInfo['street'],
		            		'city' => $billingInfo['city'],
		            		'state' => $billingInfo['region'],
		            		'zip' => $billingInfo['postcode'],
		            		'phoneNumber' => $billingInfo['telephone']
		            ),
		        ),
		    ));

		return $xml;
	
	}	
	
	/**
	 * Voids the AIM transaction
	 * @param int $amount Amount to charge
	 * @param int $payment
	 * @param int $order Order Id
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createVoidAIM($amount, $payment, $order)
	{	
		$refundTransactionId = $payment->getRefundTransactionId();
	
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
	    $xml->createTransactionRequest(array(
	        'transactionRequest' => array(
	            'transactionType' => 'voidTransaction',
	            'refTransId' => $refundTransactionId
	        ),
	    ));
		
		return $xml;
	
	}
	
	/**
	 * Refunds the AIM transaction
	 * @param int $amount Amount to charge
	 * @param int $payment
	 * @param int $order Order Id
	 * @return Mage_Authorizenetcim_Model_Authorizenetcim_Authnetxml
	 */
	
	public function createRefundAIM($amount, $payment, $order)
	{
		$refundTransactionId = $payment->getRefundTransactionId();
		$ccLast4 = $payment->getCcLast4();
		$ccExpDate = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT) . $payment->getCcExpYear();
	
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml', array('store' => $this->_storeId));
	    $xml->createTransactionRequest(array(
	        'transactionRequest' => array(
	            'transactionType' => 'refundTransaction',
	            'amount' => $amount,
	            'payment' => array(
	                'creditCard' => array(
	                    'cardNumber' => 'XXXX'.$ccLast4,
	                	'expirationDate' => $ccExpDate
	                )
	            ),
	            'refTransId' => $refundTransactionId
	        ),
	    ));
		
		return $xml;
	
	}
	
	/**
	 * Not currently used
	 * (non-PHPdoc)
	 * @see Mage_Payment_Model_Method_Cc::validate()
	 */
	
	public function validate(){}
	
	public function getCustomerPaymentProfileRequest($profileId, $paymentId)
	{
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
		$xml->getCustomerPaymentProfileRequest(array(
				'customerProfileId' => $profileId,
				'customerPaymentProfileId' => $paymentId
				));
		$billing = Mage::helper('authorizenetcim')->billingResponse($xml);
		return $billing;
	}
}
