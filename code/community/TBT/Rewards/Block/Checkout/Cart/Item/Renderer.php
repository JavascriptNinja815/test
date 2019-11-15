<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 *
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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkout Cart
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Checkout_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{

    protected $redeemed_points = null;
    protected $earned_points   = null;

    public function getRedeemedPoints()
    {
        $this->redeemed_points = Mage::helper('rewards')->unhashIt($this->getItem()->getRedeemedPointsHash());
        return $this->redeemed_points;
    }

    public function hasRedemptions()
    {
        $hasRedeemed = (sizeof($this->getRedeemedPoints()) > 0);
        return $hasRedeemed;
    }

    public function cartHasAnyCatalogRedemptions()
    {
        return $this->_getRewardsSess()->getQuote()->hasAnyAppliedCatalogRedemptions();
    }

    /**
     * Fetches the row total for the item before any catalog redemption rule
     * discounts have taken effect.
     * @return String
     */
    public function getRowTotalBeforeRedemptions()
    {
        $price = $this->getItem()->getRowTotalBeforeRedemptions();
        if ($this->helper('tax')->priceIncludesTax() && Mage::helper('tax')->displayPriceIncludingTax()) {
            $price = $this->getItem()->getRowTotalBeforeRedemptionsInclTax();
        }
        if (floatval($price) == 0) {
            $price = $this->getItem()->getRowTotal();
            if ($this->helper('tax')->priceIncludesTax() && Mage::helper('tax')->displayPriceIncludingTax()) {
                $price = $this->getItem()->getRowTotalInclTax();
            }
        }
        $price = Mage::app()->getStore()->formatPrice($price);

        return $price;
    }

    public function getEarnedPoints()
    {
        $_item               = $this->getItem();
        $this->earned_points = Mage::helper('rewards/transfer')->getEarnedPointsOnItem($_item);

        return $this->earned_points;
    }

    public function hasEarnedPoints()
    {
        $hasEarned = (sizeof($this->getEarnedPoints()) > 0);
        return $hasEarned;
    }

    public function hasEarnings()
    {
        return $this->hasEarnedPoints();
    }

    public function getEarningData()
    {
        $earned_points      = $this->getEarnedPoints();
        $earned_points_data = array();

        // We do this instead of just using the pointsString function becasue we want
        // each currency to appear on a seperate line.
        foreach ($earned_points as $cid => $points_qty) {
            $earned_points_str     = (string)Mage::getModel('rewards/points')->set(array($cid => $points_qty));
            $earned_points_data [] = $earned_points_str;
        }

        return $earned_points_data;
    }

    public function getRedemptionData()
    {
        $_item                = $this->getItem();
        $redeemed_points      = $this->getRedeemedPoints();
        $redeemed_points_data = array();

        foreach ($redeemed_points as $point) {
            if (!$point) {
                continue;
            }

            $point = (array)$point;
            $rule  = Mage::getModel('rewards/catalogrule_rule')->load($point ['rule_id']);
            if (!$rule->getId()) {
                continue;
            }

            $points_amt           = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT];
            $item_has_redemptions = true;
            $points_qty           = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT]
                                    * $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY];

            $discount          = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_EFFECT];
            $points_applic_qty = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY];

            $base_item_price = $_item->getBaseCalculationPrice();
            if ($this->helper('tax')->priceIncludesTax() && (Mage::helper('tax')->displayPriceIncludingTax()
                    || Mage::helper('tax')->displayBothPrices())
            ) {
                $base_item_price *= (1 + $_item->getTaxPercent() / 100);
            }

            $adjusted_price = Mage::helper('rewards')->priceAdjuster($base_item_price, $discount, false);

            if ($adjusted_price < 0) {
                $adjusted_price = 0;
            }

            $discount = ($base_item_price - $adjusted_price) * $points_applic_qty;
            $discount = $this->_getAggregatedCart()->getStore()->convertPrice($discount);
            $discount = $this->_getAggregatedCart()->getStore()->formatPrice($discount, false);

            $rule_id         = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID];
            $inst_id         = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_INST_ID];
            $currency_id     = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_CURRENCY_ID];
            $points_str      = Mage::getModel('rewards/points')->set($currency_id, $points_qty);
            $unit_points_str = Mage::getModel('rewards/points')->set($currency_id, $points_amt);

            $img_html               = $this->genRuleCtrlImg($rule_id, false, false, $_item->getId(), $inst_id);
            $redeemed_points_data[] = array(
                'currency_id'     => $currency_id,
                'points'          => array($currency_id => $points_qty),
                'points_str'      => $points_str,
                'discount'        => $discount,
                'img_html'        => $img_html,
                'rule'            => $rule,
                'instance_id'     => $inst_id,
                'unit_points_str' => $unit_points_str
            );
        }

        return $redeemed_points_data;
    }

    /**
     * Aggregation Cart instance
     * @return TBT_Rewards_Model_Sales_Aggregated_Cart
     */
    protected function _getAggregatedCart()
    {
        return Mage::getSingleton('rewards/sales_aggregated_cart');
    }

    /**
     * Generates that the user can click on to apply or remove rules
     *
     * @param int  $rule_id
     * @param bool $is_add
     * @param bool $is_cart
     * @param int  $item_id
     *
     * @return TBT_Rewards_Block_Checkout_Cart_Rulectrlimg $this
     */
    public function genRuleCtrlImg($rule_id, $is_add = true, $is_cart = true, $item_id = 0, $redemption_instance_id = 0, $callback = "true")
    {
        $img_block_class = 'rewards/checkout_cart_rulectrlimg';
        $img_block       = Mage::getBlockSingleton($img_block_class);
        $img_html        = $img_block->init($rule_id, $is_add, $is_cart, $item_id, $redemption_instance_id, $callback)
            ->toHtml();

        return $img_html;
    }

    public function showEarnedUnderSpent()
    {
        return Mage::helper('rewards/cart')->showPointsAdditionalSubsection();
    }

    public function isOneRedemptionMode()
    {
        $points_as_price        = $this->getCfgHelper()->showPointsAsPrice();
        $one_redemption_only    = $this->getCfgHelper()->forceOneRedemption();
        $force_redemptions      = $this->getCfgHelper()->forceRedemptions();
        $is_one_redemption_mode = ($points_as_price && $one_redemption_only && $force_redemptions);

        return $is_one_redemption_mode;
    }

    /**
     * Any type of redemptions, cart and catalog
     * @return bool
     */
    public function cartHasRedemptions()
    {
        return $this->_getRewardsSess()->hasRedemptions();
    }

    /**
     * Any type of distributions, cart and catalog
     * @return bool
     */
    public function cartHasDistributions()
    {
        return $this->_getRewardsSess()->hasDistributions();
    }

    public function showPointsColumn()
    {
        return Mage::helper('rewards/cart')->showPointsColumn();
    }

    public function showBeforePointsColumn()
    {
        return Mage::helper('rewards/cart')->showBeforePointsColumn();
    }

    /**
     * Fetchtes the rewards cofnig helper
     *
     * @return TBT_Rewards_Helper_Config
     */
    public function getCfgHelper()
    {
        return Mage::helper('rewards/config');
    }

    /**
     * Fetches the rewards session singleton
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSess()
    {
        return Mage::getSingleton('rewards/session');
    }

    public function getRowTotalInclTax($_item)
    {
        $base_row_total          = $_item->getRowTotal();
        $tax_amount              = $_item->getTaxAmount();
        $base_row_total_incl_tax = $base_row_total + $tax_amount;

        return $base_row_total_incl_tax;
    }

    public function getCurrencyCaption()
    {
        $currencyId = null;
        $redemptionData = $this->getRedemptionData();
        foreach ($redemptionData as $data) {
            if (isset($data['currency_id'])) {
                $currencyId = $data['currency_id'];
                break;
            }
        }

        if (is_null($currencyId)) {
            return '';
        }

        return Mage::helper('rewards/currency')->getCurrencyCaption($currencyId);
    }

    /**
     * Checks if a product is a points-only product.
     * @return bool     Returns true if it's a points-only product, false otherwise.
     */
    public function getIsPointsOnly()
    {
        if (!$this->hasRedemptions()) {
            return false;
        }

        $pointsOnly = Mage::getModel('rewardsonly/catalog_product')->wrap2($this->getProduct())
            ->getSimplePointsCost(Mage::getSingleton('rewards/session')->getCustomer());

        if (empty($pointsOnly)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the price of the currently rendered product in Points.
     * @return TBT_Rewards_Model_Points
     */
    public function getItemPointsPrice()
    {
        $pointsPrice    = null;
        $redemptionData = $this->getRedemptionData();
        foreach ($redemptionData as $id => $data) {
            if (isset($data['unit_points_str'])) {
                $pointsPrice = $data['unit_points_str'];
                break;
            }
        }

        return $pointsPrice;
    }

    /**
     * Returns the subtotal of the currently rendered product in Points.
     * @return TBT_Rewards_Model_Points
     */
    public function getItemSubtotalPointsPrice()
    {
        $pointsPrice    = null;
        $redemptionData = $this->getRedemptionData();
        foreach ($redemptionData as $id => $data) {
            if (isset($data['points_str'])) {
                $pointsPrice = $data['points_str'];
                break;
            }
        }

        return $pointsPrice;
    }
}
