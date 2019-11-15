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
 * Adminhtml Bundle Option Radio Type Renderer for PointsOnly Price
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Adminhtml_Catalog_Product_Type_Bundle_Option_Radio
    extends Mage_Bundle_Block_Adminhtml_Catalog_Product_Composite_Fieldset_Options_Type_Radio
{
    /**
     * Returns the formatted string for the quantity chosen for the given selection
     *
     * @param Mage_Catalog_Model_Proudct $_selection
     * @param bool                       $includeContainer
     * @return string
     */
    public function getSelectionQtyTitlePrice($_selection, $includeContainer = true)
    {
        $price = $this->getProduct()->getPriceModel()->getSelectionPreFinalPrice($this->getProduct(), $_selection);

        $this->setFormatProduct($_selection);
        $priceTitle = $_selection->getSelectionQty() * 1 . ' x ' . $this->escapeHtml($_selection->getName());

        /**
         * Begin Rewards Points Only Integration
         */
        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            $pointsArr = array();
        } else {
            $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
                ->getPointsCost($this->getProduct(), null, null, $price);
        }

        if (!empty($pointsArr)) {
            $pointsPriceStr = $pointsArr['points_min_str'];

            $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '')
            . '+' . $this->_formatPointsPriceString($pointsPriceStr, $includeContainer)
            . ($includeContainer ? '</span>' : '');

        } else {
            /**
             * End Rewards Points Only Integration
             */
            $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '')
            . '+' . $this->formatPriceString($price, $includeContainer)
            . ($includeContainer ? '</span>' : '');
        }

        return $priceTitle;
    }

    /**
     * Get title price for selection product
     *
     * @param Mage_Catalog_Model_Product $_selection
     * @param bool $includeContainer
     * @return string
     */
    public function getSelectionTitlePrice($_selection, $includeContainer = true)
    {
        $price = $this->getProduct()->getPriceModel()->getSelectionPreFinalPrice($this->getProduct(), $_selection, 1);

        /**
         * Begin Rewards Points Only Integration
         */
        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            $pointsArr = array();
        } else {
            $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
                ->getPointsCost($this->getProduct(), null, null, $price);
        }

        if (!empty($pointsArr)) {
            $pointsPriceStr = $pointsArr['points_min_str'];
            $this->setFormatProduct($_selection);
            $priceTitle = $this->escapeHtml($_selection->getName());
            $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '')
                . '+' . $this->_formatPointsPriceString($pointsPriceStr, $includeContainer)
                . ($includeContainer ? '</span>' : '');
        } else {
        /**
         * End Rewards Points Only Integration
         */
            $this->setFormatProduct($_selection);
            $priceTitle = $this->escapeHtml($_selection->getName());
            $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '')
                . '+' . $this->formatPriceString($price, $includeContainer)
                . ($includeContainer ? '</span>' : '');

        /**
         * Begin Rewards Points Only Integration
         */
        }
        /**
         * End Rewards Points Only Integration
         */

        return $priceTitle;
    }

    /**
     *
     * @param string $pointsPrice
     * @param bool $includeContainer
     * @return string
     */
    protected function _formatPointsPriceString($pointsPrice, $includeContainer = true)
    {
        if (!$includeContainer) {
            return $pointsPrice;
        }

        return '<span class="price">' . $pointsPrice . '</span>';
    }
}
