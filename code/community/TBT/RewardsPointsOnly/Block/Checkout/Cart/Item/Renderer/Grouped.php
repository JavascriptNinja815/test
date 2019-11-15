<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkout Cart Grouped Item PointsOnly Renderer
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer_Grouped
    extends TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer
{
    /**
     * Grouped Product Image
     */
    const GROUPED_PRODUCT_IMAGE = 'checkout/cart/grouped_product_image';

    /**
     * Use Parent Image
     */
    const USE_PARENT_IMAGE = 'parent';

    /**
     * Get item grouped product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getGroupedProduct()
    {
        if ($option = $this->getItem()->getOptionByCode('product_type')) {
            return $option->getProduct();
        }

        return $this->getProduct();
    }

    /**
     * Get product thumbnail image
     *
     * @return Mage_Catalog_Model_Product_Image
     */
    public function getProductThumbnail()
    {
        $product = $this->getProduct();
        if (
            !$product->getData('thumbnail') || ($product->getData('thumbnail') == 'no_selection')
            || (Mage::getStoreConfig(self::GROUPED_PRODUCT_IMAGE) == self::USE_PARENT_IMAGE)
        ) {
            $product = $this->getGroupedProduct();
        }

        return $this->helper('catalog/image')->init($product, 'thumbnail');
    }

    /**
     * Get url to item product
     *
     * @return string
     */
    public function getProductUrl()
    {
        return $this->getGroupedProduct()->getProductUrl();
    }

    /**
     * Prepare item html
     *
     * This method uses renderer for real product type
     *
     * @return string
     */
    protected function _toHtml()
    {
        $renderer = $this->getRenderedBlock()->getItemRenderer($this->getItem()->getRealProductType());
        $renderer->setItem($this->getItem());
        $renderer->overrideProductUrl($this->getProductUrl());
        $renderer->overrideProductThumbnail($this->getProductThumbnail());
        $rendererHtml = $renderer->toHtml();
        $renderer->overrideProductUrl(null);
        $renderer->overrideProductThumbnail(null);
        
        return $rendererHtml;
    }
}
