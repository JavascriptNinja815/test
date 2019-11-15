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
 * Checkout Cart Configurable Item PointsOnly Renderer
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer_Configurable
    extends TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer
{
    /**
     * Configurable Product Image
     */
    const CONFIGURABLE_PRODUCT_IMAGE = 'checkout/cart/configurable_product_image';

    /**
     * Use Parent Image
     */
    const USE_PARENT_IMAGE = 'parent';

    /**
     * Get item configurable product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getConfigurableProduct()
    {
        if ($option = $this->getItem ()->getOptionByCode('product_type')) {
            return $option->getProduct();
        }

        return $this->getProduct();
    }

    /**
     * Get item configurable child product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getChildProduct()
    {
        if ($option = $this->getItem()->getOptionByCode('simple_product')) {
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
        $product = $this->getChildProduct();

        if (
            !$product || !$product->getData('thumbnail')
            || ($product->getData('thumbnail') == 'no_selection')
            || (Mage::getStoreConfig(self::CONFIGURABLE_PRODUCT_IMAGE) == self::USE_PARENT_IMAGE)
        ) {
            $product = $this->getProduct();
        }

        return $this->helper('catalog/image')->init($product, 'thumbnail');
    }

    /**
     * Get item product name
     *
     * @return string
     */
    public function getProductName()
    {
        return $this->getProduct()->getName();
    }

    /**
     * Get url to item product
     *
     * @return string
     */
    public function getProductUrl()
    {
        return $this->getProduct()->getProductUrl();
    }

    /**
     * Get selected for configurable product attributes
     *
     * @return array
     */
    public function getProductAttributes()
    {
        $attributes = $this->getProduct()->getTypeInstance(true)
            ->getSelectedAttributesInfo($this->getProduct());

        return $attributes;
    }

    /**
     * Get list of all otions for product
     *
     * @return array
     */
    public function getOptionList()
    {
        $options = array_merge($this->getProductAttributes(), $this->getProductOptions());

        return $options;
    }
}
