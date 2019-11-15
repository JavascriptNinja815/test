<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time Sweet Tooth spent
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension.
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * Purchase Share Block
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Block_Purchase extends Mage_Checkout_Block_Onepage_Success
{
    /**
     * Is purchase sharing enabled?
     * @return bool
     */
    public function isPurchaseSharingEnabled()
    {
        $purchaseButtons = Mage::getStoreConfig('rewards/rewardssocial2/purchase_buttons');
        return !empty($purchaseButtons);
    }

    /**
     * Fetch column count
     * @return int
     */
    public function getColumnCount() 
    {
        return 3;
    }

    /**
     * Create the sharing buttons for a product
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return string (HTML)
     */
    public function getSocialButtonsHtml($product)
    {
        return $this->getLayout()->createBlock('rewardssocial2/social')
            ->setTemplate('rewardssocial2/sharing.phtml')
            ->setActionType('purchase')
            ->setData('product', $product)
            ->setData('order_id', $this->getOrderId())
            ->toHtml();
    }

    /**
     * Fetch the order that was just made
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        return Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
    }

    /**
     * Wrapper for standard strip_tags() function with extra functionality for html entities
     *
     * @param string $data
     * @param string $allowableTags
     * @param bool $escape
     * @return string
     */
    public function stripTags($data, $allowableTags = null, $escape = false)
    {
        $result = strip_tags($data, $allowableTags);
        return $escape ? $this->escapeHtml($result, $allowableTags) : $result;
    }
    
    /**
     * Fetch all products to render on checkout success
     * @return array[Mage_Catalog_Model_Product]
     */
    public function getProductsToRender()
    {
        $productsToRender = $groupedProductsMap = array();
        $orderItemsCollection = $this->getOrder()->getAllVisibleItems();
        
        foreach ($orderItemsCollection as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            
            // Render visible products only
            if ($product->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                $productsToRender[] = $product;
            }
            
            // If we are dealing with grouped products, save product information for later processing
            if ($item->getProductType() == Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE) {
                $parentId = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($item->getProductId());
                
                if (!array_key_exists($parentId[0], $groupedProductsMap)) {
                    $groupedProductsMap[$parentId[0]] = false;
                }
                
                $groupedProductsMap[$parentId[0]] = $groupedProductsMap[$parentId[0]] 
                    || ($product->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
            }
        }
        
        /* 
         * When dealing with grouped products, we will render all visible child products
         * unless all child products are hidden, then we will render the parent product
         */
        foreach ($groupedProductsMap as $productId => $skip) {
            if ($skip) {
                continue;
            }
            
            $product = Mage::getModel('catalog/product')->load($productId);
            $productsToRender[] = $product;
        }
        
        return $productsToRender;
    }
}
