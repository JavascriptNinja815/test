<?php /* Collins Harper
* http://www.collinsharper.com 
*/ ?>
<?php class Mage_StorePickup_Model_Carrier_ShippingMethod extends Mage_Shipping_Model_Carrier_Abstract
{ 
    const RV_PREFIX = 'RV';
protected $_code = 'storepickupmodule';
 

public function collectRates(Mage_Shipping_Model_Rate_Request $request)
 {
 if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
	return false;
 }
 if (!Mage::app()->getStore()->isAdmin() && !Mage::getStoreConfig('carriers/'.$this->_code.'/activefe')) {
	return false;
 } 
 
 if(!$this->checkAvailableShipCountries($request))
 {
	return false;

 }

	//Check for free shipping
	//$cart = Mage::getModel('checkout/cart')->getQuote();
	$cart = Mage::getModel('checkout/session')->getQuote();
	$quoteId = $cart->getId();
	$items = $cart->getAllItems();
	$hasRvCard = false;
	//Get giftcards from quote
	$cards = Mage::helper('aw_giftcard/totals')->getQuoteGiftCards($quoteId);

	foreach($cards as $item) {
		$gcCode = $item->getCode();
		if(substr($gcCode, 0, 2) == self::RV_PREFIX) {
			$hasRvCard = true;
		}
	}

	if(!$hasRvCard) {
		return false;
	}

     $validFree = false;
     if (count($items) == 1) {
         /** @var \Mage_Sales_Model_Quote_Item $item0 */
         $item0 = $items[0];
         if (
             $item0->getProduct()->getName() == 'Reels Only'
             && $item0->getQty() == 1
             && $request->getDestCountryId() == 'US'
         ) {
             $validFree = true;
         }
     }

	if($hasRvCard && !$validFree) {
		$result = Mage::getModel('shipping/rate_result');
		$error = Mage::getModel('shipping/rate_result_error');
		$error->setCarrier($this->_code);
		$error->setCarrierTitle($this->getConfigData('title'));
		$errorMsg = $this->getConfigData('specificerrmsg');
		$error->setErrorMessage($errorMsg);
		$result->append($error);
		return $error;
	} else {
		$result = Mage::getModel('shipping/rate_result');
	}

$handling = 0;
 if(Mage::getStoreConfig('carriers/'.$this->_code.'/handling') >0) $handling = Mage::getStoreConfig('carriers/'.$this->_code.'/handling');

if(Mage::getStoreConfig('carriers/'.$this->_code.'/handling_type') == 'P' && $request->getPackageValue() > 0)
 $handling = $request->getPackageValue()*$handling;
 $rate = Mage::getModel('shipping/rate_result_method');

$rate->setCarrier($this->_code);
 $rate->setCarrierTitle(Mage::getStoreConfig('carriers/'.$this->_code.'/title'));

$rate->setMethod('pickup');
 $rate->setMethodTitle(Mage::getStoreConfig('carriers/'.$this->_code.'/methodtitle'));

$rate->setCost($handling);
 $rate->setPrice($handling);
 $result->append($rate);
 return $result;
 
 } 
 
 
     public function checkAvailableShipCountries(Mage_Shipping_Model_Rate_Request $request)
    {
        $speCountriesAllow = $this->getConfigData('sallowspecific');

        /*
        * for specific countries, the flag will be 1
        */
		mage::log("well" . $speCountriesAllow);

		mage::log("well" . $this->getConfigData('specificcountry') );

		
        if($speCountriesAllow && $speCountriesAllow==1){
             $showMethod = $this->getConfigData('showmethod');

             $availableCountries = array();

             if( $this->getConfigData('specificcountry') ) {
                $availableCountries = explode(',',$this->getConfigData('specificcountry'));

             }
             if ($availableCountries && in_array($request->getDestCountryId(), $availableCountries)) {
                 return $this;

             } elseif ($showMethod && (!$availableCountries || ($availableCountries && !in_array($request->getDestCountryId(), $availableCountries)))){
                   $error = Mage::getModel('shipping/rate_result_error');

                   $error->setCarrier($this->_code);

                   $error->setCarrierTitle($this->getConfigData('title'));

                   $errorMsg = $this->getConfigData('specificerrmsg');

                   $error->setErrorMessage($errorMsg?$errorMsg:Mage::helper('shipping')->__('The shipping module is not available for selected delivery country.'));

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
        return array('pickup'=>Mage::helper('shipping')->__('Store Pickup'));

    }


	}
