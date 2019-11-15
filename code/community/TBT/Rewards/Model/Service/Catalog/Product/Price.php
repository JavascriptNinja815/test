<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Used to generate final price for all types of catalog products
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Service_Catalog_Product_Price
    extends Varien_Object
{
    /**
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $buyRequest
     * @return array
     */
    public function getPricesForProduct(Mage_Catalog_Model_Product $product, $buyRequest = null, $useMaxPrice = false)
    {
        $newProduct = clone $product;
        
        switch ($product->getTypeId()) {
            case 'simple':
                $price = $this->_getSimpleProductPrice($newProduct, $buyRequest);
                break;
            case 'configurable':
                $price = $this->_getConfigurableProductPrice($newProduct, $buyRequest);
                break;
            case 'bundle':
                $price = $this->_getBundleProductPrice($newProduct, $buyRequest, $useMaxPrice);
                break;
            case 'grouped':
                $price = $this->_getGroupedProductPrice($newProduct, $buyRequest);
                break;
            case 'virtual':
                $price = $this->_getVirtualProductPrice($product, $buyRequest);
            default:
                $price = $this->_getSimpleProductPrice($newProduct, $buyRequest);
                break;
        }

        if (Mage::helper('tax')->priceIncludesTax($product->getStore())) {
            $priceExclTax = floatval(Mage::helper('tax')->getPrice($product, $price));
            $priceInclTax = floatval($price);
        } else {
            $priceExclTax = floatval($price);
            $priceInclTax = floatval(Mage::helper('tax')->getPrice($product, $price, true));
        }

        return array($priceExclTax, $priceInclTax);
    }

    /**
     * Get Simple Product Final Price
     * @param Mage_Catalog_Model_Product $product
     * @param array $buyRequest
     * @return float
     */
    protected function _getSimpleProductPrice(Mage_Catalog_Model_Product $product, $buyRequest)
    {
        return $product->getPriceModel()->getBasePrice($product);
    }

    protected function _getVirtualProductPrice(Mage_Catalog_Model_Product $product, $buyRequest)
    {
        return $product->getPriceModel()->getBasePrice($product);
    }

    /**
     * Get Bundle Product Final Price
     * @param Mage_Catalog_Model_Product $product
     * @param array $buyRequest
     * @return float
     */
    protected function _getBundleProductPrice(Mage_Catalog_Model_Product $product, $buyRequest, $useMaxPrice = false)
    {
        if ($product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
            return $product->getPriceModel()->getBasePrice($product);
        }

        /**
         * Continue to calculate Dynamic Price
         */
        
        $price = 0.0;

        if (isset($buyRequest['bundle_option']) && isset($buyRequest['bundle_option_qty'])) {
            $selectionIds = array_values($buyRequest['bundle_option']);
            $selectionQtys = $buyRequest['bundle_option_qty'];

            if (empty($selectionIds)) {
                return $price;
            }

            $selections = $product->getTypeInstance(true)->getSelectionsByIds($selectionIds, $product);
            $selections->addTierPriceData();

            foreach ($selections as $selection) {
                if ($selection->isSalable()) {
                    $selectionQty = (isset($selectionQtys[$selection->getOptionId()])) ? $selectionQtys[$selection->getOptionId()] : 1;

                    $price += $product->getPriceModel()->getSelectionFinalTotalPrice(
                        $product, $selection, 1, $selectionQty
                    );
                }
            }

            return $price;
        }

        /**
         * There is no BuyRequest, Calculate Price as Max Price or Based on Default Selection
         */

        $options = $product->getTypeInstance(true)->getOptions($product);
        $selections = $product->getTypeInstance(true)->getSelectionsByIds(array(), $product);
        $selections->addTierPriceData();

        foreach ($options as $option) {
            $selection = $this->_getBundleSelectionForOptimalPrice($option, $selections, $useMaxPrice);

            if ($selection && $selection->isSalable()) {
                $selectionQty = ($selection->getSelectionQty()) ? $selection->getSelectionQty() : 1;

                $price += $product->getPriceModel()->getSelectionFinalTotalPrice(
                    $product, $selection, 1, $selectionQty
                );
            }
        }

        return $price;
    }

    /**
     *
     * @param Mage_Bundle_Model_Option $option
     * @param array $selections
     * @param boolean $useMaxPrice
     * @return Mage_Bundle_Model_Selection
     */
    private function _getBundleSelectionForOptimalPrice($option, $selections, $useMaxPrice = false)
    {
        $auxMaxSelection = null;
        $auxMaxSelectionPrice = 0.0;

        foreach ($selections as $selection) {
            if ($selection->getOptionId() === $option->getOptionId()) {
                if (!$useMaxPrice) {
                    if ($selection->getIsDefault()) {
                        $auxMaxSelection = $selection;
                        break;
                    }
                } else {
                    if ($selection->getPrice() >= $auxMaxSelectionPrice) {
                        $auxMaxSelection = $selection;
                        $auxMaxSelectionPrice = $selection->getPrice();
                    }
                }
            }
        }

        return $auxMaxSelection;
    }

    /**
     * Get Configurable Product Final Price
     * @param Mage_Catalog_Model_Product $product
     * @param array $buyRequest
     * @return float
     */
    protected function _getConfigurableProductPrice(Mage_Catalog_Model_Product $product, $buyRequest)
    {
        $price = 0.0;
        $productType = $product->getTypeInstance(true);

        $attributes = $productType->getConfigurableAttributes($product);

        foreach ($attributes as $attribute) {
            if (
                !isset($buyRequest['supper_attribute'])
                || !isset($buyRequest['supper_attribute'][$attribute->getProductAttribute()->getAttributeId()])
            ) {
                $defaultValue = $attribute->getProductAttribute()->getDefaultValue();

                if ($defaultValue) {
                    $buyRequest['super_attribute'][$attribute->getProductAttribute()->getAttributeId()] = $defaultValue;
                }
            }
        }

        $buyRequest['qty'] = 1;
        $buyRequest['product'] = $product->getId();

        $buyRequestObj = new Varien_Object($buyRequest);

        $productType->prepareForCartAdvanced($buyRequestObj, $product, 'lite');

        $price = $product->getFinalPrice();

        return $price;
    }

    /**
     * Get Grouped Product Final Price
     * @param Mage_Catalog_Model_Product $product
     * @param array $buyRequest
     * @return float
     */
    protected function _getGroupedProductPrice(Mage_Catalog_Model_Product $product, $buyRequest)
    {
        $price = 0.0;

        $price += $product->getFinalPrice();

        $productType = $product->getTypeInstance(true);

        $associatedProducts = $productType->getAssociatedProducts($product);

        foreach ($associatedProducts as $associatedProduct) {
            if (
                !isset($buyRequest['super_group'])
                || !isset($buyRequest['supper_attribute'][$associatedProduct->getId()])
            ) {
                $buyRequest['super_group'][$associatedProduct->getId()] = $associatedProduct->getQty();

                $price += $associatedProduct->getPriceModel()->getBasePrice($associatedProduct) * $associatedProduct->getQty();
            } else {
                $price += $associatedProduct->getPriceModel()->getBasePrice($associatedProduct) * floatval($buyRequest['super_group'][$associatedProduct->getId()]);
            }
        }

        return $price;
    }

    /**
     * Get Configurable Product Base price
     * @param Mage_Catalog_Model_Product $product
     * @param boolean $includeParentPrice
     * @return float
     */
    public function getConfigurableBasePrice($product, $includeParentPrice = true, $qty = null)
    {
        $price = 0.0;

        $basePrice = $product->getPriceModel()->getBasePrice($product, $qty);

        if ($basePrice < 0.00001) {
            $basePrice = $product->getInitialPrice();
        }

        if ($includeParentPrice) {
            $price += $basePrice;
        }

        $product->getTypeInstance(true)
                ->setStoreFilter($product->getStore(), $product);
        $attributes = $product->getTypeInstance(true)
                ->getConfigurableAttributes($product);

        $selectedAttributes = array();
        if ($product->getCustomOption('attributes')) {
            $selectedAttributes = unserialize($product->getCustomOption('attributes')->getValue());
        }

        foreach ($attributes as $attribute) {
            $attributeId = $attribute->getProductAttribute()->getId();
            $value = $this->_getValueByIndex(
                $attribute->getPrices() ? $attribute->getPrices() : array(),
                isset($selectedAttributes[$attributeId]) ? $selectedAttributes[$attributeId] : null
            );

            if ($value) {
                if ($value['pricing_value'] != 0) {
                    $price += $this->_calcSelectionPrice(
                        $value,
                        $basePrice
                    );
                }
            }
        }
        
        return $price;
    }

    protected function _applyGroupSpecialTierPrices($product, $price, $qty)
    {
        /* Group Price */
        $groupPrice = $price;

        $groupPriceStr = $product->getGroupPrice();

        if (is_numeric($groupPriceStr) && $groupPriceStr > 0.00001) {
            $groupPrice = min($groupPrice, $groupPriceStr);
        }

        /* Tier Price */
        $tierPrice = $price;

        if (!is_null($qty)) {
            $tierPriceStr = $product->getTierPrice($qty);
            
            if (is_numeric($tierPriceStr) && $tierPriceStr > 0.00001) {
                $tierPrice = min($tierPrice, $tierPriceStr);
            }
        }

        /* Special Price */
        $specialPrice = $product->getPriceModel()->calculateSpecialPrice(
                $price, $product->getSpecialPrice(), $product->getSpecialFromDate(),
                $product->getSpecialToDate(), $product->getStore()
            );

        $price = min($price, $groupPrice, $tierPrice, $specialPrice);

        return $price;
    }

    /**
     * Calculate configurable product selection price
     *
     * @param   array $priceInfo
     * @param   decimal $productPrice
     * @return  decimal
     */
    protected function _calcSelectionPrice($priceInfo, $productPrice)
    {
        if($priceInfo['is_percent']) {
            $ratio = $priceInfo['pricing_value']/100;
            $price = $productPrice * $ratio;
        } else {
            $price = $priceInfo['pricing_value'];
        }
        return $price;
    }

    /**
     * Get Value by Index
     * @param array $values
     * @param integer $index
     * @return boolean
     */
    protected function _getValueByIndex($values, $index)
    {
        foreach ($values as $value) {
            if($value['value_index'] == $index) {
                return $value;
            }
        }
        return false;
    }
}
