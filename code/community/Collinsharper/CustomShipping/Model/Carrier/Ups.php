<?php /* Collins Harper
* http://www.collinsharper.com
*/
/**
 * Extended UPS shipping implementation
 *
 * @category   Mage
 * @package    Mage_Usa
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collinsharper_CustomShipping_Model_Carrier_Ups
    extends Mage_Usa_Model_Shipping_Carrier_Ups
{
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag($this->_activeFlag)) {
            return false;
        }
        // KL: Check the PO Box address
        if (!$this->getConfigValue('allow_po_ups')) {
            // KL: Check PO Box
            if (preg_match("/p\.* *o\.* *box/i", $request->getDestStreet())) {
                return false;
            }
        }

        return parent::collectRates($request);
    }

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
