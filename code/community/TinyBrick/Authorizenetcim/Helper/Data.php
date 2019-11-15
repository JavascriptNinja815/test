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
class TinyBrick_Authorizenetcim_Helper_Data extends Mage_Core_Helper_Abstract
{
    const FORCE_LOGGING = true;
    const ADDITIONAL_LOGGING_FILE_NAME = "custom_authnet.log";
    public function debugLog($filename, $linenumber, $message) {
        if ((self::FORCE_LOGGING)) {
            $logFile = Mage::getBaseDir("log").DS.self::ADDITIONAL_LOGGING_FILE_NAME;
            file_put_contents($logFile,
                date("Y-m-d H:i")." ".$filename." ".$linenumber."\n".$message."\n\n",
                FILE_APPEND);
        }
    }
    /**
     * Checks the response for errors. if found, throws an exception
     * @param string $response
     */
    public function response($response, $request = false)
    {

        mage::log(__METHOD__ . __LINE__  . " response " . print_r($response,1));
        mage::log(__METHOD__ . __LINE__  . " request " . print_r($request,1));
        if(!$response->isSuccessful()){
            $result = $response->messages->resultCode;
            $resultCode = $response->messages->message->code;
            $resultText = $response->messages->message->text;

            Mage::throwException($this->_getErrorMessage('Result: '.$result.' Code: '.$resultCode.' Message: '.$resultText));
        }
        else if($response->messages->resultCode != 'Ok'){

            $errorCode = $response->transactionResponse->errors->error->errorCode;
            $errorText = $response->transactionResponse->errors->error->errorText;

            Mage::throwException($this->_getErrorMessage('Error Code: '.$errorCode.' Error Text: '.$errorText));
        }
    }


    public function _getErrorMessage($string)
    {
        $session = Mage::getSingleton('checkout/session');
        $additionalMessage = '';
        if($session->getAuthNetCimError()) {
            $additionalMessage = "\n If you have continual errors submitting your order, \n please contact our Customer Service Department for assistance at 1-855-733-5748 \n between the hours of 8am and 5pm PST Monday through Friday.";
        }
        $session->setAuthNetCimError(true);
        return $string . $additionalMessage;
    }

    /**
     * Checks the response for errors. if found, throws an exception.
     * @param string $response
     */
    public function result($response)
    {

        $session = Mage::getSingleton('checkout/session');
        $session->setAuthNetCimFailure(false);

        if(!$response->isSuccessful()) {

            $session->setAuthNetCimFailure((string)$session->getAuthNetCimCreatedOrderId());

            $result = $response->messages->resultCode;
            $resultCode = $response->messages->message->code;
            $resultText = $response->messages->message->text;

            Mage::throwException($this->_getErrorMessage('Result: '.$result.' Code: '.$resultCode.' Message: '.$resultText));

        } else {
            // clear the bits?
            $session->setAuthNetCimProfileId(false);
            $session->setAuthNetCimTokenId(false);
        }
    }

    public function billingResponse($response){
        $billing = array(
            'address' => (string) $response->paymentProfile->billTo->address,
            'city' => (string) $response->paymentProfile->billTo->city,
            'state' => (string) $response->paymentProfile->billTo->state,
            'zip' => (string) $response->paymentProfile->billTo->zip
        );
        return $billing;
    }

}
