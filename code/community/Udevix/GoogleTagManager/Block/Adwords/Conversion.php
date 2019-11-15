<?php
/**
 * Udevix
 *
 * @author     UdevixTeam <udevix@gmail.com>
 * @copyright  Copyright (c) 2015-2016 Udevix
 */

/**
 * Class Udevix_GoogleTagManager_Block_Adwords_Conversion
 */
class Udevix_GoogleTagManager_Block_Adwords_Conversion extends Mage_Core_Block_Template
{
    /**
     * Return json data for adwords conversion tracking
     *
     * @return bool|string
     */
    public function getConversionData()
    {
        $result = array(
            'event'                   => 'fireAdwordsConversionTag',
            'google_conversion_value' => 0
        );

        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        if ($orderId) {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order')->loadByAttribute('increment_id', $orderId);

            $result['google_conversion_value'] = round($order->getGrandTotal(), 2);
            $result['google_conversion_currency'] = $order->getOrderCurrencyCode();
        }

        return json_encode($result);
    }
}
