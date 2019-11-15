<?php

class Collinsharper_Upsaddressvalidation_Model_Soap_Validation
{

    const WSDL = 'XAV.wsdl';
    const USER_ADDRESS_VALID = 1;
    const USER_ADDRESS_CORRECTED = 2;
    const USER_ADDRESS_INVALID = 3;

    protected $wsdl;
    protected $endpoint;
    protected $_helper;

    public function __construct()
    {
        Mage::log(__METHOD__ . __LINE__ );

        $this->wsdl = dirname(dirname(dirname(__FILE__))) . DS . 'etc' . DS . self::WSDL;
        $this->endpoint = 'https://wwwcie.ups.com/webservices/XAV';
        $this->_helper = Mage::helper('chupsaddressvalidation');

        if($this->isTest()) {
            $this->endpoint = 'https://onlinetools.ups.com/webservices/XAV';
        }
    }

    private function isTest()
    {
        return $this->getUpsConfigData('mode_xml') != 1;
    }

    private function isDebug()
    {
        return $this->getConfigData('debug') != 1;
    }

    private function getUpsUsername()
    {
        return $this->getUpsConfigData('username');
    }

    private function getUpsPassword()
    {
        return $this->getUpsConfigData('password');
    }

    private function getUpsLicense()
    {
        return $this->getUpsConfigData('access_license_number');
    }

    private function getUpsConfigData($x)
    {
        return Mage::GetStoreConfig('carriers/ups/'.$x);
    }

    function setAddressAsTested($address)
    {
        $valid_pass = Mage::getSingleton('core/session')->getUpsAddrValidationPass();
        $valid_pass[$this->createAddressHash($address)] = true;
        Mage::getSingleton('core/session')->setUpsAddrValidationPass($valid_pass);
    }

    function hasAddressBeenTested($address = false)
    {
        $address = $this->_getAddress();
        $valid_pass = Mage::getSingleton('core/session')->getUpsAddrValidationPass();
        $hash = $this->createAddressHash($address);
        mage::log(__METHOD__ . " we have " . $hash );
        return isset($valid_pass[$hash]) && $valid_pass[$hash] == true;
    }

    private function getConfigData($x)
    {
        return $this->_helper->getConfigData($x);
    }

    function createAddressHash($address = false)
    {
        if(!$address) {
            $address = $this->_getAddress();
        }

        $street = $address->getStreet();
        if(!is_array($street)) {
            $street = explode("\n",$street);
        }

        if(count($street) != 3) {
            for($i=0;$i<3;$i++) {
                $street[$i] = isset($street[$i]) ? $street[$i] : '';
            }
        }

        return md5($address->getFirstname() . ' ' . $address->getLastname() . serialize($street) .
            $address->getCity() . $address->getRegionCode() . $address->getPostcode() . $address->getCountryId());
    }

    function processXAV($address)
    {
        //create soap request
        $option['RequestOption'] = '1';
        $request['Request'] = $option;

        //$request['RegionalRequestIndicator'] = '';

        // KL: Provide Name only if we had information
        $addrkeyfrmt['ConsigneeName'] = $address->getFirstname() . ' ' . $address->getLastname();

        // get all streets then loop into array?
        $street = $address->getStreet();

        if(!is_array($street)) {
            $street = explode("\n",$street);
        }

        if(count($street) != 3) {
            for($i=0;$i<3;$i++) {
                $street[$i] = isset($street[$i]) ? $street[$i] : '';
             }
        }
        $addrkeyfrmt['AddressLine'] = $street;

        //$addrkeyfrmt['Region'] = 'ROSWELL,GA,30075-1521'; // pass this or those.. not both.
        //$addrkeyfrmt['PoliticalDivision2'] = 'ALISO VIEJO'; // city
        $addrkeyfrmt['PoliticalDivision2'] =  $address->getCity(); // city
        $addrkeyfrmt['PoliticalDivision1'] =  $address->getRegionCode(); // STATE
        $addrkeyfrmt['PostcodePrimaryLow'] =  $address->getPostcode();
       // $addrkeyfrmt['Region'] = $address->getCity() . ' ' . $address->getRegionCode() . ' ' . $address->getPostcode();

        // $addrkeyfrmt['PostcodeExtendedLow'] = '1521'; // the 4 digit code.. we dont use that..
        //  $addrkeyfrmt['Urbanization'] = 'porto arundal'; // only valid for puerto rico
        $addrkeyfrmt['CountryCode'] =  $address->getCountryId();

        $request['AddressKeyFormat'] = $addrkeyfrmt;

        $this->log(__METHOD__ . __LINE__ . " Address Validation Request: " . print_r($request,1));

        return $request;
    }

    function _getAddress()
    {
        if (!Mage::helper('chupsaddressvalidation')->isAdmin()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
        } else {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }

        if(!$quote) {
            throw new Exception("Could not find a quote address to validate.");
        }

        $address = $quote->getShippingAddress();

        if(!$address) {
            $this->log(__METHOD__ . __LINE__ . " switch to billing address? ");
            $address = $quote->getBillingAddress();
        }

        return $address;
    }

    function validateShippingAddress()
    {
        // frontend other wise?
        //if(!$this->_is_admin) {

        $address = $this->_getAddress();
        $this->setAddressAsTested($address);
        return $this->validateAddress($address);
    }

    function validateAddress($address)
    {
        if(!$this->getUpsUsername() ||
           !$this->getUpsPassword() ||
           !$this->getUpsLicense() ) {
            throw new Exception ("Magento core UPS is not configured for XML functionality. Please disable the UPS address Validation Module.");
        }
        
        try {

        $mode = array (
            'soap_version' => 'SOAP_1_1',  // use soap 1.1 client
            'trace' => 1
        );


        $operation = "ProcessXAV";

        $client = new SoapClient($this->wsdl , $mode);

        $client->__setLocation($this->endpoint);

        //create soap header
        $usernameToken['Username'] = $this->getUpsUsername();
        $usernameToken['Password'] = $this->getUpsPassword();
        $serviceAccessLicense['AccessLicenseNumber'] = $this->getUpsLicense();
        $upss['UsernameToken'] = $usernameToken;
        $upss['ServiceAccessToken'] = $serviceAccessLicense;
            $this->log(__METHOD__ . __LINE__ . " Address Validation Response: " . print_r($upss,1));
        $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0','UPSSecurity',$upss);
        $client->__setSoapHeaders($header);

        //get response
            Mage::log(__METHOD__ . __LINE__ );
        $requestObject = $this->processXAV($address);
        $response = $client->__soapCall($operation ,array($requestObject));
            Mage::log(__METHOD__ . __LINE__ );
        $this->log(__METHOD__ . __LINE__ . " Address Validation Response: " . print_r($response,1));

        return $this->_parseResponse($response, $requestObject);

        } catch (Exception $e) {
            Mage::log(__METHOD__ . __LINE__ . " Exception: " . $e->getMessage());
        }
        return true;
    }

    function _parseResponse($response, $requestObject)
    {
        // decide if the address is valid, do we warn or force change?
        $_hadMatching = false;
        $_result = array();
        $_result["result"] = true;

        // Do we have any return address?
        if (isset($response->NoCandidatesIndicator)) {
            Mage::getSingleton('core/session')->setUpsAddressVaidaton(self::USER_ADDRESS_INVALID);
            $_result["result"] = false;
            return $_result;
        }

        $_requestAddress = $requestObject["AddressKeyFormat"];
        if ($response->Response->ResponseStatus->Code == "1") {
            foreach ($response->Candidate as $addresses) {
                // Over come the Array and non-Array return
                if (isset($addresses->AddressKeyFormat)) {
                    $address = $addresses->AddressKeyFormat;
                } else {
                    $address = $addresses;
                }

                // Clean up
                if (is_array($address->AddressLine)) {
                    $address->AddressLine = trim(implode(" ", $address->AddressLine));
                }
                $_result["Candidate"][] = $address;

                // Check to see if customer may already enter the right address
                if (strtolower($address->AddressLine) == strtolower(trim(implode(" ", $_requestAddress["AddressLine"]))) &&
                    strtolower($address->PoliticalDivision2) == strtolower($_requestAddress["PoliticalDivision2"]) &&
                    strtolower($address->PoliticalDivision1) == strtolower($_requestAddress["PoliticalDivision1"])) {
                    $_hadMatching = true;
                }
            }

            if (isset($_result["Candidate"])) {
                if (!$_hadMatching) {
                    Mage::getSingleton('core/session')->setUpsAddressVaidaton(self::USER_ADDRESS_INVALID);
                    $_result["result"] = false;
                } else {
                    if (Mage::getSingleton('core/session')->getUpsAddressVaidaton() == self::USER_ADDRESS_INVALID) {
                        Mage::getSingleton('core/session')->setUpsAddressVaidaton(self::USER_ADDRESS_CORRECTED);
                    } else {
                        Mage::getSingleton('core/session')->setUpsAddressVaidaton(self::USER_ADDRESS_VALID);
                    }
                }
            }
        }

        return $_result;
    }

    function log($x)
    {
        if($this->isTest() || $this->isDebug()) {
            mage::log($x);
        }
    }
}
