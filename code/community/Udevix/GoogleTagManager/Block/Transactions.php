<?php
/**
 * Udevix
 *
 * @author     UdevixTeam <udevix@gmail.com>
 * @copyright  Copyright (c) 2015-2016 Udevix
 */

/**
 * Class Udevix_GoogleTagManager_Block_Transactions
 */
class Udevix_GoogleTagManager_Block_Transactions extends Mage_Checkout_Block_Success
{
    /**
     * @var int
     */
    protected $lastOrderId = 0;

    /**
     *
     */
    function __construct()
    {
        $this->lastOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
    }

    /**
     * Return json data for transactions
     *
     * @return string
     */
    public function getTransactionsData()
    {
        /**
         * @var Udevix_GoogleTagManager_Helper_Data $helper
         */
        $helper = Mage::helper('udevix_google_tag_manager');
        $data = array();

        if ($this->lastOrderId) {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order')->loadByAttribute('increment_id', $this->lastOrderId);
            $items = $order->getAllVisibleItems();
            $products = array();

            /**
             * @var Mage_Sales_Model_Order_Item $item
             */
            foreach ($items as $item) {
                $price = $item->getPrice();

                /**
                 * @var Mage_Catalog_Model_Product $product
                 */
                $product = $item->getProduct();

                $categoryIds = $product->getCategoryIds();
                $categoryId = $categoryIds[sizeof($categoryIds) - 1];

                /**
                 * @var Mage_Catalog_Model_Category $category
                 */
                $category = Mage::getModel('catalog/category')->load($categoryId);

                $products[] = array(
                    'sku'      => $item->getSku(),
                    'name'     => $item->getName(),
                    'category' => $category->getName(),
                    'price'    => round($price, 2),
                    'quantity' => (int)$item->getQtyOrdered()
                );
            }

            $data = array(
                'transactionId'          => $order->getIncrementId(),
                'transactionAffiliation' => $helper->getTransactionAffiliation(),
                'transactionTotal'       => max(round($order->getGrandTotal(), 2), 0),
                'transactionTax'         => round($order->getTaxAmount(), 2),
                'transactionShipping'    => round($order->getShippingAmount(), 2),
                'currencyCode'           => $order->getOrderCurrencyCode(),
                'transactionProducts'    => $products
            );
        }

        $data = json_encode($data);

        return $data;
    }
}
