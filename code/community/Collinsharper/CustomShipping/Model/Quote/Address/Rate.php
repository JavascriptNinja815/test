<?php

/**
 * Override Quote Address Rate to update Shipping Title for UPS
 *
 * @category   Collinsharper
 * @package    Collinsharper_CustomShipping
 * @author     Rudie Wang <rwang@collinsharper.com>
 */
class Collinsharper_CustomShipping_Model_Quote_Address_Rate
    extends Mage_Sales_Model_Quote_Address_Rate
{
    public function importShippingRate(Mage_Shipping_Model_Rate_Result_Abstract $rate)
    {
        if ($rate instanceof Mage_Shipping_Model_Rate_Result_Error) {
            $this
                ->setCode($rate->getCarrier().'_error')
                ->setCarrier($rate->getCarrier())
                ->setCarrierTitle($rate->getCarrierTitle())
                ->setErrorMessage($rate->getErrorMessage())
            ;
        } elseif ($rate instanceof Mage_Shipping_Model_Rate_Result_Method) {
            // CH: Add method description
            $methodDescription = Mage::getStoreConfig('carriers/ups/ups_'.$rate->getMethod(), Mage::app()->getStore()->getStoreId());
            if ($methodDescription != "") {
                $methodTitl = preg_replace("/\([^)]+\)/","",$rate->getMethodTitle());
                $methodTitle = $methodTitl. ' ' . $methodDescription;
            } else {
                $methodTitle = $rate->getMethodTitle();
            }
            // CH
            $this
                ->setCode($rate->getCarrier().'_'.$rate->getMethod())
                ->setCarrier($rate->getCarrier())
                ->setCarrierTitle($rate->getCarrierTitle())
                ->setMethod($rate->getMethod())
                ->setMethodTitle($methodTitle)
                ->setMethodDescription($rate->getMethodDescription())
                ->setPrice($rate->getPrice())
            ;
        }
        return $this;
    }
}
