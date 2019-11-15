<?php
/**
 * Udevix
 *
 * @author     UdevixTeam <udevix@gmail.com>
 * @copyright  Copyright (c) 2015-2016 Udevix
 */

/**
 * Class Udevix_GoogleTagManager_Block_Pixel_Cart
 */
class Udevix_GoogleTagManager_Block_Pixel_Cart extends Mage_Core_Block_Template
{
    /**
     * Return data for tracking additions to cart
     *
     * @return bool|string
     */
    public function getCartAddProductData()
    {
        // Get added products
        $products = Mage::getSingleton('core/session')->getGtmCartAddProducts();

        $result = false;

        if (!is_null($products)) {
            // Get base currency
            $currencyCode = Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);

            $ids = array();
            $price = 0;

            foreach ($products as $product) {
                $ids[] = $product['id'];
                $price += $product['price'];
            }

            $result = json_encode(array(
                'event'            => 'udxAddToCart',
                'udx_value'        => round($price, 2),
                'udx_currency'     => $currencyCode,
                'udx_content_type' => 'product',
                'udx_content_ids'  => $ids,
            ));

            Mage::getSingleton('core/session')->unsGtmCartAddProducts();

            return $result;
        }

        return $result;
    }
}
