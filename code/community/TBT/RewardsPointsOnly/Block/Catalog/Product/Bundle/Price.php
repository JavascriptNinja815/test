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
 * Frontend Price renderer for PointsOnly
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Catalog_Product_Bundle_Price
    extends TBT_Rewards_Block_Catalog_Product_Bundle_Price
{
    /**
     * Block Html generator
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            return parent::_toHtml();
        }
        
        $blockName = $this->getNameInLayout();

        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($this->getProduct());

        if(empty($pointsArr)) {
            return parent::_toHtml();
        }

        if (strpos($this->getTemplate(), "tierprices.phtml") !== false) {
    		return '';
    	}

        if (
            strpos($this->getTemplate(), "price.phtml") === false
            && strpos($this->getTemplate(), "price_configured.phtml") === false
        ) {
            return parent::_toHtml();
        }

        $this->setPointsOnlyInfo($pointsArr);

        $pointsPriceTemplate = 'rewardspointsonly/catalog/product/bundle/price.phtml';

        if ($blockName === 'bundle.prices') {
            $pointsPriceTemplate = 'rewardspointsonly/catalog/product/view/type/bundle/price_configured.phtml';
        }

        $this->setTemplate($pointsPriceTemplate);

        $this->setRewardsRestrictPredictions(true);

        return parent::_toHtml();
    }

    /**
     * Get Product Points Only String Cost
     * @return string
     */
    protected function getPointsOnlySimpleCostMin()
    {
        $pointsArr = $this->getPointsOnlyInfo();

        return $pointsArr['points_min_str'];
    }

    /**
     * Get Product Points Only String Cost
     * @return string
     */
    protected function getPointsOnlySimpleCostMax()
    {
        $pointsArr = $this->getPointsOnlyInfo();

        return $pointsArr['points_max_str'];
    }

    /**
     * Get Points Only Price for Configured Bundle Options
     * @return string
     */
    protected function getPointsOnlySimpleCostConfigured()
    {
        $taxHelper = Mage::helper('tax');

        $productPrice = $this->getProduct()->getPriceModel()->getBasePrice($this->getProduct());
        $productPrice = $taxHelper->getPrice($this->getProduct(), $productPrice, $taxHelper->priceIncludesTax());

        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($this->getProduct());

        return $pointsArr['points_min_str'];
    }

    /**
     * Get Points Only Price for Configured Bundle Options
     * @return string
     */
    protected function getPointsOnlyJsonConfigMainProduct()
    {
        $taxHelper = Mage::helper('tax');

        $productPrice = $this->getProduct()->getPriceModel()->getBasePrice($this->getProduct());
        $productPrice = $taxHelper->getPrice($this->getProduct(), $productPrice, $taxHelper->priceIncludesTax());

        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($this->getProduct(), null, null, $productPrice);

        if ($this->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
            $pointsArr['price_type'] = 'fixed';
            $currencyCaption = Mage::helper('rewards/currency')->getDefaultFullCurrencyCaption();
            $pointsArr['rewardspointsonly_points_caption'] = $currencyCaption;
        } else {
            $pointsArr['price_type'] = 'dynamic';
        }

        return Mage::helper('core')->jsonEncode($pointsArr);
    }

    /**
     * Get Product Points Only Value
     * @return int
     */
    protected function getPointsOnlyValue()
    {
        $pointsArr = $this->getPointsOnlyInfo();

        return $pointsArr['points_value'];
    }
}
