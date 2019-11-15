<?php
class Collinsharper_Image3d_Model_Ups extends Mage_Usa_Model_Shipping_Carrier_Ups
{

    public function getFinalPriceWithHandlingFee($cost)
    {
        if($this->getConfigData('handling_fee_intl') && $this->_request->getDestCountryId() != self::USA_COUNTRY_ID) {
            mage::log(__METHOD__ . __LINE__ . " fee for into only.. ");
            return $cost;
        }

        $handlingFee = $this->getConfigData('handling_fee');
        $handlingType = $this->getConfigData('handling_type');
        if (!$handlingType) {
            $handlingType = self::HANDLING_TYPE_FIXED;
        }
        $handlingAction = $this->getConfigData('handling_action');
        if (!$handlingAction) {
            $handlingAction = self::HANDLING_ACTION_PERORDER;
        }

        return ($handlingAction == self::HANDLING_ACTION_PERPACKAGE)
            ? $this->_getPerpackagePrice($cost, $handlingType, $handlingFee)
            : $this->_getPerorderPrice($cost, $handlingType, $handlingFee);
    }

}
