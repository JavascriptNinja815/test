<?php

class Collinsharper_Upsaddressvalidation_Model_Observer
{

    protected $controllers = array('onepage', 'sales_order_create');
    protected $actions = array('saveBilling', 'saveShipping', 'loadBlock');
    protected $_helper;
    protected $_is_admin = false;

    public function __construct()
    {

    }


    function _help()
    {
        if(!$this->_helper) {
            $this->_helper = Mage::helper('chupsaddressvalidation');
        }

        return $this->_helper;
    }

    function _getResponse()
    {
        return Mage::app()->getResponse();
    }

    function _getRequest()
    {
        return Mage::app()->getRequest();
    }


/**
 * modified ajax responses baed on UPS address validation
 * @param $observer
 * @return Collinsharper_Upsaddressvalidation_Model_Observer
 */

    function controller_front_send_response_before($observer)
    {
        if(!$this->_help()->isAdmin()) {
            if(!$this->_help()->isFrontActive()) {
                return $this;
            }
        } else {
            if(!$this->_help()->isBackendActive()) {
                return $this;
            }
        }

        $event = $observer->getEvent();
        $front = $event->getFront();
        $controller =  $this->_getRequest()->getControllerName();
        $action =  $this->_getRequest()->getActionName();

        if(in_array($controller, $this->controllers) && in_array($action, $this->actions)) {
            $body = $front->getBody();
            $params = $this->_getRequest()->getParams();
            mage::log(__METHOD__ . "we have " . $controller . " and Action " . $action . " with strlen " . strlen($body)) ;
            $body =  $this->_getResponse()->getBody();
           // mage::log(__METHOD__ . "we have " . print_r($body,1)) ;
            $useBillingForshipping = isset($params['billing']) && isset($params['billing']['use_for_shipping']) && $params['billing']['use_for_shipping'];

            if ($action == 'saveBilling' && $useBillingForshipping || $action == 'saveShipping' || $action == 'loadBlock') {

                if($json = json_decode($body)) {
                    mage::log(__METHOD__ . "we have " . print_r($json,1)) ;
                    mage::log(__METHOD__ . "we have " . print_r($params,1)) ;
                } else {
                    // We cannot process the data if it is not JSON encoded and it is not from admin
                    if(!$this->_help()->isAdmin()) {
                        throw new Exception("Cannot process request. Invalid request");
                    }
                }

                //TODO: Ensure we only test US addresses.
                $validAddress = $this->_validateAddress();
                Mage::Log(__FILE__." ".__LINE__." ".print_r($validAddress, true), null, "kit.log");

                $validator = Mage::getModel('chupsaddressvalidation/soap_validation');
                $address = $validator->_getAddress();

                // if its not american || its been tested
                $dontForceBeenTested = $address->getCountryId() != 'US' || ((($this->_help()->isAdmin() && !$this->_help()->getConfigData("force_valid_backend")) ||
                    !$this->_help()->getConfigData("force_valid_frontend")) &&
                    $validator->hasAddressBeenTested())
                    ;

                if($dontForceBeenTested) {
                    mage::log(__METHOD__ . __LINE__ . " dont force and its been tested!");
                }

                if($validator->hasAddressBeenTested()) {
                    mage::log(__METHOD__ . __LINE__ . "  its been tested!");
                }

                if($address->getCountryId() == 'US' && !$validAddress["result"] && !$dontForceBeenTested) {
                    // KL: Prepare the block content
                    $layout = Mage::getSingleton('core/layout');
                    $block_response = $layout->createBlock('chupsaddressvalidation/index')->setTemplate('chupsaddressvalidation/address_list.phtml');
                    $block_response->setforceAddress(false);
                    $block_response->setMessageInfo($this->_helper->__('Our address verification system suggests your address might be incorrect. Please review the suggestion(s) below.'));
                     $block_response->setAddressFromUPS(array());
                    // KL: Check to see if we may have any suggested address
                    if (isset($validAddress["Candidate"])) {
                        $block_response->setAddressFromUPS($validAddress["Candidate"]);

                        // if we are in the frontend...
                        if(!$this->_help()->isAdmin()) {
                            // KL: Do we have force address on?
                            mage::log(__METHOD__ . __LINE__ . " we havve " . $this->_help()->getConfigData("force_valid_frontend"));
                            if ($this->_help()->getConfigData("force_valid_frontend") == 1) {
                                $block_response->setforceAddress(true);
                                $block_response->setMessageInfo($this->_helper->__('Please correct your Shipping Address'));
                            }

                            $htmlResponse = $block_response->toHtml();
                            $body = array(
                                "goto_section" => "shipping",
                                'error_messages' => $this->_helper->__('Please correct you shipping address.'),
                                "update_section" => array (
                                    'name' => 'address_verification',
                                    'html' => $htmlResponse)
                            );
                        } else {
                            // KL: Do we have force address on?
                            if ($this->_help()->getConfigData("force_valid_backend") == "1") {
                                $block_response->setforceAddress(true);
                                $block_response->setMessageInfo($this->_helper->__('Please correct your Shipping Address'));
                            }

                            $htmlResponse = $block_response->toHtml();
                            $json->shipping_address = $json->shipping_address."\r\n".$htmlResponse;
                            $body = $json;
                        }

                        $this->_getResponse()->setBody(json_encode($body));

                    } else {
                        // Invalid address
                        $block_response->setMessageInfo($this->_helper->__('Your address is not valid. Please correct your Shipping Address'));

                        if(!$this->_help()->isAdmin()) {
                            // KL: Do we have force address on?
                            if ($this->_help()->getConfigData("force_valid_frontend") == "1") {
                                $block_response->setforceAddress(true);
                                $htmlResponse = $block_response->toHtml();
                                $body = array(
                                    "goto_section" => "shipping",
                                    'error_messages' => $this->_helper->__('Please correct you shipping address.'),
                                    "update_section" => array (
                                        'name' => 'address_verification',
                                        'html' => $htmlResponse)
                                );

                                $this->_getResponse()->setBody(json_encode($body));
                            }
                        } else {
                            // KL: Do we have force address on?
                            if ($this->_help()->getConfigData("force_valid_backend") == "1") {
                                $block_response->setforceAddress(true);
                                $htmlResponse = $block_response->toHtml();
                                $json->shipping_address = $json->shipping_address."\r\n".$htmlResponse;
                                $body = $json;

                                $this->_getResponse()->setBody(json_encode($body));
                            }
                        }
                    }


                }
                Mage::Log(__FILE__." ".__LINE__." ups_address_validation: ".print_r(Mage::getSingleton('core/session')->getUpsAddressVaidaton(), true), null, "kit.log");
            }

        }

    }

    public function _validateAddress()
    {
        // TODO: find the address from the admin order or the frontend.
        // TODO: build a class that we can call and will use the UPS address valdiation code
        // TODO: Parese the UPS response and determine if we need to prompt the user.
        // TODO: do we force the user to correct the address?
        // TODO: do we let them pass with an invalid address?
        // TODO: record hits of failed addresses and responses in a table for review later.
        $validator = Mage::getModel('chupsaddressvalidation/soap_validation');

        return $validator->validateShippingAddress();

    }

    public function updateShippingBlockOptions($observer)
    {
        $block = $observer->getBlock();
        $transport = $observer->getTransport();

        if ($block instanceof Mage_Checkout_Block_Onepage_Shipping
        || $block instanceof Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form
    ) {
            mage::log(__METHOD__ . __LINE__ . " we have " . get_class($block ));
            $this->_is_admin  = $block instanceof Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form;
            $this->_appendHtml($block, $transport);
        }
        return $this;
    }

    protected function _appendHtml($block, $transport)
    {
        $change = false;
        $currentRate = false;
        $searchtagname = "s_method_canpar";

        $doc = new DOMDocument();
        $html = $transport->getHtml();
        // $this->_is_admin
//        @$doc->loadHTML($html);
//        // find form by ID
//        // insert div
//        //
//        $input = $doc->getElementsByTagName('form')->item(0);
//        $tagchilddoc = new DOMDocument();
//        $tagchilddoc->loadXML('<div id="cboAddress"><p></p></div>');
//        $tagchild = $tagchilddoc->getElementsByTagName('div')->item(0);
//        $ntagchild = $doc->importNode($tagchild, true);
//        $input->nextSibling->appendChild($ntagchild);
//        $dochtml = $doc->C14N();
//        $replace = array('<html>', '</html>', '<body>', '</body>');
//        $html = str_replace($replace, '', $dochtml);

        $html = "<!-- updated in " . __CLASS__ . " --> \n  <div id=\"checkout-address_verification-load\"><p></p></div> {$html} \n <!-- updated in " . __CLASS__ . " -->";
        $transport->setHtml($html);

        //mage::log(__METHOD__ .  __LINE__ .  " we have html " . $html  );
        mage::log(__METHOD__ .  __LINE__ .  " we have html "   );

    }
}
