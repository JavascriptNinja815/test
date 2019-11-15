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
 * Frontend Class for Product View Additional JS interaction with Points Only
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Catalog_Product_View_AdditionalOptionsJs
    extends Mage_Core_Block_Template
{
    /**
     * Prepare HTML
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Prepare Configurable Product PointsOnly Json Config
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function getConfigurablePointsJsonConfig($product)
    {
        $config = array();

        if (! $product instanceof Mage_Catalog_Model_Product) {
            return Mage::helper('core')->jsonEncode($config);
        }

        if ($product->getTypeId() !== 'configurable') {
            return Mage::helper('core')->jsonEncode($config);
        }

        $taxHelper = Mage::helper('tax');

        $productPrice = $this->getProduct()->getPriceModel()->getBasePrice($this->getProduct());
        $productPrice = $taxHelper->getPrice($this->getProduct(), $productPrice, $taxHelper->priceIncludesTax());
        
        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($product, null, null, $productPrice);

        if (!empty($pointsArr)) {
            $currencyCaption = Mage::helper('rewards/currency')->getDefaultFullCurrencyCaption();
            $config['rewardspointsonly_ispointsonly'] = "1";
            $config['rewardspointsonly_base_points_price_value'] = (int) $pointsArr['points_min_value'];
            $config['rewardspointsonly_points_caption'] = $currencyCaption;
            $config['product_id'] = $product->getId();
            $config['rewardspointsonly_rule_type'] = $pointsArr['points_rule_type'];
        } else {
            $config['rewardspointsonly_ispointsonly'] = "0";
            
            return Mage::helper('core')->jsonEncode($config);
        }

        $configurableAttributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);

        foreach ($configurableAttributes as $configurableAttribute) {
            $attributeId = $configurableAttribute->getAttributeId();

            $attributeOptionPrices = $configurableAttribute->getPrices();

            if (!is_array($attributeOptionPrices)) {
                continue;
            }

            foreach ($attributeOptionPrices as $attributeOptionPrice) {
                if (!$attributeOptionPrice['pricing_value']) {
                    $config['attributes'][$attributeId][$attributeOptionPrice['value_index']] = array(
                        'rewardspointsonly_points_price_value' => 0
                    );
                    continue;
                }

                $optionPrice = Mage::helper('rewards/price')->preparePriceProductTypeComposite(
                    $product, $attributeOptionPrice['pricing_value'], $attributeOptionPrice['is_percent']
                );

                $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
                    ->getPointsCost($product, null, null, $optionPrice);

                if (!empty($pointsArr)) {
                    $config['attributes'][$attributeId][$attributeOptionPrice['value_index']] = array(
                        'rewardspointsonly_points_price_value' => (int) $pointsArr['points_min_value']
                    );
                } else {
                    $config['attributes'][$attributeId][$attributeOptionPrice['value_index']] = array(
                        'rewardspointsonly_points_price_value' => 0
                    );
                }
            }
        }

        return Mage::helper('core')->jsonEncode($config);
    }

    /**
     * Prepare Grouped Product PointsOnly Json Config
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function getGroupedPointsJsonConfig($product)
    {
        $config = array();

        if (! $product instanceof Mage_Catalog_Model_Product) {
            return Mage::helper('core')->jsonEncode($config);
        }

        if ($product->getTypeId() !== 'grouped') {
            return Mage::helper('core')->jsonEncode($config);
        }

        $productType = $product->getTypeInstance(true);
        $associatedProducts = $productType->getAssociatedProducts($product);

        $currencyCaption = Mage::helper('rewards/currency')->getDefaultFullCurrencyCaption();
        $config['rewardspointsonly_points_caption'] = $currencyCaption;
        $config['parent_product_id'] = $product->getId();

        foreach ($associatedProducts as $associatedProduct) {
            $price = floatval($associatedProduct->getPriceModel()->getBasePrice($associatedProduct));

            $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
                ->getPointsCost($associatedProduct, null, null, $price);

            if (!empty($pointsArr)) {
                $config['rewardspointsonly_ispointsonly'] = "1";
                $config['associated_products'][$associatedProduct->getId()] = array(
                    'rewardspointsonly_points_price_value' => (int) $pointsArr['points_min_value']
                );
                $config['rewardspointsonly_rule_type'] = $pointsArr['points_rule_type'];
            } else {
                $config['rewardspointsonly_ispointsonly'] = "0";
                $config['associated_products'][$associatedProduct->getId()] = array(
                    'rewardspointsonly_points_price_value' => 0
                );
            }
        }

        return Mage::helper('core')->jsonEncode($config);
    }

    /**
     * Customer Points Balance Available (balance - in use points from cart)
     * @return int
     */
    protected function getCustomerPointsBalanceAvailable()
    {
        $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        $rewardsSession = Mage::getSingleton('rewards/session');

        $customer = $rewardsSession->getCustomer();

        if (!$customer || !$customer->getId()) {
            return 0;
        }
        
        if (!$customer->hasUsablePoints()) {
            return 0;
        }

        $usableBalance = $customer->getUsablePoints();
        $pointsSpending = $rewardsSession->getTotalPointsSpending();

        if (empty($pointsSpending)) {
            $pointsSpending[$defaultCurrencyId] = 0;
        }

        $availableBalance = $usableBalance[$defaultCurrencyId] - $pointsSpending[$defaultCurrencyId];

        return $availableBalance;
    }

    /**
     * Retrieve Product
     * @return Mage_Catalog_Model_Product|null
     */
    protected function getProduct()
    {
        $product = Mage::registry('current_product');

        return $product;
    }
}
