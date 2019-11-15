<?php
/**
 * Udevix
 *
 * @author     UdevixTeam <udevix@gmail.com>
 * @copyright  Copyright (c) 2015-2016 Udevix
 */

/**
 * Class Udevix_GoogleTagManager_Block_Pixel
 */
class Udevix_GoogleTagManager_Block_Pixel extends Mage_Core_Block_Template
{
    /**
     * Return event data
     *
     * @return bool|array
     */
    public function getEventData()
    {
        $result = array(
            'event' => '',
            'data'  => 0
        );

        // Get type of opened page
        $pageType = $this->getPageType();

        // Generate data by page type
        switch ($pageType) {
            case 'product':
                /**
                 * @var Mage_Catalog_Model_Product $product
                 */
                $product = Mage::registry('current_product');

                $price = $product->getFinalPrice();

                $currencyCode = Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);

                // if Bundle Product - get minimal price
                if ($product->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $price = Mage::getModel('bundle/product_price')->getTotalPrices($product, 'min', 1);
                }

                $categoryIds = $product->getCategoryIds();

                /**
                 * @var Mage_Catalog_Model_Category $category
                 */
                $category = Mage::getModel('catalog/category')->load($categoryIds[0]);

                $result = json_encode(array(
                    'event'                => 'udxViewContent',
                    'udx_value'            => round($price, 2),
                    'udx_currency'         => $currencyCode,
                    'udx_content_name'     => $product->getName(),
                    'udx_content_category' => $category->getName(),
                    'udx_content_type'     => 'product',
                    'udx_content_ids'      => array($product->getSku()),
                ));

                break;

            case 'initiate_checkout':
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

                $currencyCode = Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);

                $result = json_encode(array(
                    'event'            => 'udxInitiateCheckout',
                    'udx_value'        => round($grandTotal, 2),
                    'udx_currency'     => $currencyCode,
                    'udx_content_type' => 'product',
                    'udx_content_ids'  => $ids,
                ));

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

                    $result = json_encode(array(
                        'event'            => 'udxPurchase',
                        'udx_value'        => round($order->getGrandTotal(), 2),
                        'udx_currency'     => $order->getOrderCurrencyCode(),
                        'udx_content_type' => 'product',
                        'udx_content_ids'  => $ids,
                    ));
                }

                break;

            default:
                return false;
        }

        return $result;
    }
}
