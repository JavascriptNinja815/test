<?php
/**
 * Udevix
 *
 * @author     UdevixTeam <udevix@gmail.com>
 * @copyright  Copyright (c) 2015-2016 Udevix
 */

/**
 * Class Udevix_GoogleTagManager_Block_Remarketing
 */
class Udevix_GoogleTagManager_Block_Remarketing extends Mage_Core_Block_Template
{
    /**
     * Return json data for remarketing
     *
     * @return bool|string
     */
    public function getRemarketingData()
    {
        $result = array(
            'event'             => 'fireRemarketingTag',
            'google_tag_params' => array()
        );

        // Get type of opened page
        $pageType = $this->getPageType();

        // Generate data by page type
        switch ($pageType) {
            case 'home':
                $result['google_tag_params'] = array(
                    'ecomm_pagetype' => $pageType
                );

                break;

            case 'category':
                /**
                 * @var Mage_Eav_Model_Entity_Collection_Abstract $products
                 */
                $products = Mage::getBlockSingleton('catalog/product_list')->getLoadedProductCollection();

                $ids = array();

                /**
                 * @var Mage_Catalog_Model_Product $item
                 */
                foreach ($products as $item) {
                    $ids[] = $item->getSku();
                }

                if (empty($ids)) {
                    return false;
                }

                $result['google_tag_params'] = array(
                    'ecomm_prodid'   => $ids,
                    'ecomm_pagetype' => $pageType
                );

                break;

            case 'product':
                $product = Mage::registry('current_product');

                $price = $product->getFinalPrice();

                // if Bundle Product - get minimal price
                if ($product->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $price = Mage::getModel('bundle/product_price')->getTotalPrices($product, 'min', 1);
                }

                $result['google_tag_params'] = array(
                    'ecomm_prodid'     => $product->getSku(),
                    'ecomm_pagetype'   => $pageType,
                    'ecomm_totalvalue' => round($price, 2),
                );

                break;

            case 'cart':
                /**
                 * @var Mage_Sales_Model_Quote $quote
                 */
                $quote = Mage::getSingleton('checkout/cart')->getQuote();
                $grandTotal = $quote->getGrandTotal();
                $products = $quote->getAllItems();
                $ids = array();

                /**
                 * @var Mage_Catalog_Model_Product $item
                 */
                foreach ($products as $item) {
                    $ids[] = $item->getSku();
                }

                $ids = array_unique($ids);

                if (empty($ids)) {
                    return false;
                }

                $result['google_tag_params'] = array(
                    'ecomm_prodid'     => $ids,
                    'ecomm_pagetype'   => $pageType,
                    'ecomm_totalvalue' => round($grandTotal, 2),
                );

                break;

            case 'purchase':
                $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

                if ($orderId) {
                    /**
                     * @var Mage_Sales_Model_Order $order
                     */
                    $order = Mage::getModel('sales/order')->loadByAttribute('increment_id', $orderId);
                    $items = $order->getAllItems();

                    $ids = array();

                    foreach ($items as $item) {
                        $ids[] = $item->getProductId();
                    }

                    $products = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('sku')
                        ->addIdFilter($ids);

                    $ids = array();

                    /**
                     * @var Mage_Catalog_Model_Product $item
                     */
                    foreach ($products as $item) {
                        $ids[] = $item->getSku();
                    }

                    $ids = array_unique($ids);

                    if (empty($ids)) {
                        return false;
                    }

                    $result['google_tag_params'] = array(
                        'ecomm_prodid'     => $ids,
                        'ecomm_pagetype'   => $pageType,
                        'ecomm_totalvalue' => round($order->getGrandTotal(), 2),
                    );
                }

                break;

            default:
                return false;
        }

        return json_encode($result);
    }
}
