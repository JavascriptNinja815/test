<?php
/**
 * Collinsharper/Chusps/Model/Shipping/Carrier/Usps.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chusps
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Chusps_Model_Shipping_Carrier_Ups
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chusps
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Chusps_Model_Shipping_Carrier_Usps
    extends Mage_Usa_Model_Shipping_Carrier_Usps
{
    /**
     * Collect and get rates this function has been override to provide qty order restriction
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        Mage::helper('chusps')->debugLog(__FILE__ . ' ' .__LINE__ );
        if (!$this->getConfigFlag($this->_activeFlag)) {
            Mage::helper('chusps')->debugLog(__FILE__ . ' ' .__LINE__ );
            return false;
        }

        //KL: Check to see if we had total qty more then 38? and it is for internation customer?
        if ($request->getOrigCountry()) {
            $origCountry = $request->getOrigCountry();
        } else {
            $origCountry = Mage::getStoreConfig(
                Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID,
                $request->getStoreId()
            );
        }

        if ($request->getDestCountryId()) {
            $destCountry = $request->getDestCountryId();
        } else {
            $destCountry = self::US_COUNTRY_ID;
        }

        Mage::helper('chusps')->debugLog(__FILE__ . ' ' .__LINE__ . ' origCountry' . print_r($origCountry,true));
        Mage::helper('chusps')->debugLog(__FILE__ . ' ' .__LINE__ . ' destCountry' . print_r($destCountry,true));
        if ($origCountry != $destCountry) {
            $total_qty = 0;
            if ($request->getAllItems()) {
                $total_length = 0;
                foreach ($request->getAllItems() as $item) {
                    if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                        continue;
                    }

                    $total_qty += $item->getQty();
                }

                if ($total_qty > 38) {
                    return false;
                }
            }
        }

        $this->setRequest($request);

        $this->_result = $this->_getQuotes();

        $this->_updateFreeMethodQuote($request);

        return $this->getResult();
    }

}
