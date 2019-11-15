<?php /* Collins Harper
* http://www.collinsharper.com
*/


class Collinsharper_CustomShipping_Model_Carrier_ShippingMethod extends Mage_Shipping_Model_Carrier_Abstract
{
    const CODE = 'chcustomshippingmodule';
    const METHOD = 'method';
    const SHIPPING_FIELD_COUNT = 4;
    const SHIPPING_FIELD_METHOD = 0;
    const SHIPPING_FIELD_PRICE = 1;
    const SHIPPING_FIELD_COUNTRY = 2;
    const SHIPPING_FIELD_SKUS = 3;
    const SHIPPING_FIELD_WILDCARD = '*';
    const SHIPPING_FIELD_SEP = '|';
    const SHIPPING_FIELD_DELIM = ';';
    const SHIPPING_FIELD_SKU_QTY_DELIM = ':';


    protected $_code = self::CODE;

    protected $_raw_request;


    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigValue('active')) {
            return false;
        }
        
        if (!Mage::app()->getStore()->isAdmin() && !$this->getConfigValue('activefe')) {
            return false;
        }


        if(!$this->checkAvailableShipCountries($request)) {
            return false;

        }

        $this->_raw_request = $request;

        $methods = $this->getEligibleMethods();
        if(!$methods) {
            return false;
        }


        $result = Mage::getModel('shipping/rate_result');

        $handling = 0;
        if($this->getConfigValue('handling') > 0) {
            $handling = $this->getConfigValue('handling');
        }

        if($this->getConfigValue('handling_type') == 'P' && $request->getPackageValue() > 0) {
            $handling = $request->getPackageValue()*$handling;
        }
        $methodCounter = 0;
        
        foreach($methods as $title => $price) {
            $rate = Mage::getModel('shipping/rate_result_method');

            $rate->setCarrier($this->_code);
            $rate->setCarrierTitle($this->getConfigValue('title'));

            $rate->setMethod(self::METHOD . $methodCounter);

            $rate->setMethodTitle($title);

            $rate->setCost($handling + $price);
            $rate->setPrice($handling + $price);
            $result->append($rate);
            $methodCounter++;
        }

        return $result;

    }



    public function getEligibleMethods()
    {
        $validMethods = null;
        $shippingMethodData = $this->getConfigValue('custom_shipping_methods');
        if(!$shippingMethodData) {
            return false;
        }

        $shippingMethodData = explode("\n", $shippingMethodData);

        if(!count($shippingMethodData)) {
            return false;
        }

        foreach($shippingMethodData as $k =>  $rawRow) {
            $hasError = array();
            $rawRow  = str_replace("\r", '', trim($rawRow));

            $row = explode(self::SHIPPING_FIELD_SEP, $rawRow);
            if(count($row) != self::SHIPPING_FIELD_COUNT) {
                $hasError[] = 'Field count is wrong ' . count($row) . ' - ' . self::SHIPPING_FIELD_COUNT;
                continue;
            }
	 
            // KL: Check PO Box
            $carrier_name = explode(' ', $row[self::SHIPPING_FIELD_METHOD]);
            if (preg_match("/^\s*((?:P(?:OST)?.?\s*(?:O(?:FF(?:ICE)?)?)?.?\s*(?:B(?:IN|OX)?)?)+|(?:B(?:IN|OX)+\s+)+)\s*/i", $this->_raw_request->getDestStreet())) {
                if (trim($carrier_name[0]) == 'UPS') {
                    continue;
                }
            }

            $countryList = explode(self::SHIPPING_FIELD_DELIM, $row[self::SHIPPING_FIELD_COUNTRY]);

            if(!$countryList) {
                $countryList = array(self::SHIPPING_FIELD_WILDCARD);
            }

            $productList = array();
            $productData = explode(self::SHIPPING_FIELD_DELIM, $row[self::SHIPPING_FIELD_SKUS]);
            foreach($productData as $prodX) {
                // USPS First Class (Est. delivery 3 – 5 days)|4.25|US|REEL:1-20
                $productQty = explode(self::SHIPPING_FIELD_SKU_QTY_DELIM, $prodX);
                $qtyRange = explode("-", trim($productQty[1]));
                if(!count($qtyRange) || (int)$qtyRange[0] < 0) {
                    $hasError[] = 'qty is wrorng . ' .json_encode($qtyRange);
                }
                $productList[trim($productQty[0])] = array('qty' => 0, 'min' => $qtyRange[0], 'max' => isset($qtyRange[1]) ? $qtyRange[1] : false) ;
            }

	    // KL: Check to see if we have exclude country
	    $validCountry = false;
            if (substr($countryList[0], 0, 1) == '!') {
                if ($this->_raw_request->getDestCountryId() != substr($countryList[0], 1)) {
                    $validCountry = true;
                }
            } else {
                $validCountry = $countryList[0] == self::SHIPPING_FIELD_WILDCARD || in_array($this->_raw_request->getDestCountryId(), $countryList);
            }

            $validCartProductsAndAllRules = $validCountry && $this->_validateCartProductRule($productList);

            if($validCartProductsAndAllRules) {
                $validMethods[$row[self::SHIPPING_FIELD_METHOD]] = (double) trim($row[self::SHIPPING_FIELD_PRICE]);
            }

            if(count($hasError) || !$validCartProductsAndAllRules) {

                $hasValidProducts = $validCartProductsAndAllRules ? 'yes' : 'no';
                $hasValidCountry = $validCountry ? 'yes' : 'no';
                $ruleData = json_encode($rawRow);
                $errorData = json_encode($hasError);
                $message = "error parsing rule at line $k - $errorData  {$ruleData} - hasValidProducts $hasValidProducts   $hasValidCountry = $validCountry ";
                $this->log(__METHOD__ . __LINE__ . " " . $message);
                mage::logException(new Exception($message));
                continue;
            }
        }

        if ($validMethods != null) {
            return $validMethods;
        }else {
            return false;
        }
    }


    public function _validateCartProductRule($productList)
    {
        // We have something like
        // // USPS First Class (Est. delivery 3 – 5 days)|4.25|US|REEL:1-20
        // $productList['REEL'] = array('min' => 1, 'max' => 20);

        $hasOtherProducts = false;
        $hasFailedProducts = false;
        $this->log(__METHOD__ . __LINE__  . ' ' . json_encode($productList));

        if ($this->_raw_request->getAllItems()) {
            foreach ($this->_raw_request->getAllItems() as $item) {
                list($sku, $crap) = explode('-', $item->getProduct()->getSku(), 2);

                $this->log(__METHOD__ . __LINE__ . " testing $sku for " . $item->getQty());

                // KL: We need to collect all information first
                $hasRule = isset($productList[$sku]);

                if($hasRule) {
                    $productList[$sku]['qty'] = $productList[$sku]['qty'] + $item->getQty();
                } else {
                    $hasOtherProducts = true;
                }
            }

            // Now, we do the check
            foreach ($productList as $sku => $data) {
                $meetsMin = $productList[$sku]['min'] == 0 || $productList[$sku]['qty'] >= $productList[$sku]['min'];
                $maxValue = $productList[$sku]['max'];
                $meetsMax = $maxValue == false || $productList[$sku]['qty'] <= $maxValue;
                if( !$meetsMin || !$meetsMax) {
                    $hasFailedProducts = true;
                }
            }
        }

        $this->log(__METHOD__ . __LINE__ );
        return !$hasFailedProducts && !$hasOtherProducts;
    }

    public function getConfigValue($x)
    {
        return Mage::getStoreConfig('carriers/' . $this->_code . '/' . $x);
    }

    public function log($x)
    {
        Mage::log($x, null, 'ch_custom_shipping.log');
    }
    
    public function checkAvailableShipCountries(Mage_Shipping_Model_Rate_Request $request)
    {
        $speCountriesAllow = $this->getConfigData('sallowspecific');

        $this->log(__METHOD__ . __LINE__ . " " . $speCountriesAllow);

        $this->log(__METHOD__ . __LINE__ . " " . $this->getConfigData('specificcountry') );


        if($speCountriesAllow && $speCountriesAllow == 1) {
            $showMethod = $this->getConfigData('showmethod');

            $availableCountries = array();

            if( $this->getConfigData('specificcountry') ) {

                $availableCountries = explode(',',$this->getConfigData('specificcountry'));

            }

            if ($availableCountries && in_array($request->getDestCountryId(), $availableCountries)) {

                return $this;

            } elseif ($showMethod && (!$availableCountries || ($availableCountries && !in_array($request->getDestCountryId(), $availableCountries)))) {

                $error = Mage::getModel('shipping/rate_result_error');

                $error->setCarrier($this->_code);

                $error->setCarrierTitle($this->getConfigData('title'));

                $errorMsg = $this->getConfigData('specificerrmsg');

                $error->setErrorMessage(
                    $errorMsg ?
                        $errorMsg :
                        Mage::helper('shipping')->__('The shipping module is not available for selected delivery country.')
                );

                return $error;

            } else {
                /*
               * The admin set not to show the shipping module if the devliery country is not within specific countries
               */
                return false;

            }
        }
        return $this;

    }


    public function getAllowedMethods()
    {
        $list = array();
        for($i=0;$i<11;$i++) {
            $list[self::METHOD . $i] =  Mage::helper('shipping')->__('Method ' . $i);
        }
            return $list;
    }


}
