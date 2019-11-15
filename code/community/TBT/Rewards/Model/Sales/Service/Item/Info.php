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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class is used as a service to retrieve information about points applied on cart item
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Sales_Service_Item_Info
    extends Mage_Core_Model_Abstract
{
    /**
     * Quote Item
     * @var Mage_Sales_Model_Quote_Item 
     */
    protected $_item;
    
    /**
     * Earning Points cached
     * @var mixed 
     */
    protected $_earnedPoints;
    
    /**
     * Catalog Redemption Data
     * @var array 
     */
    protected $_catalogRedemptionData;
    
    /**
     * Main Constructor
     * @param Mage_Sales_Model_Quote_Item $item
     */
    public function __construct($item)
    {
        parent::__construct();
        
        $this->_item = $item;
    }
    
    /**
     * Check if item has catalog redemptions applied
     * @return boolean
     */
    public function hasCatalogRedemptions()
    {
        return sizeof($this->getCatalogRedemptionData()) > 0 ? true : false;
    }
    
    /**
     * Catalog Redemptions List applied on cart item
     * @return array
     */
    public function getCatalogRedemptionData()
    {
        if ($this->_catalogRedemptionData) {
            return $this->_catalogRedemptionData;
        }
        
        $item = $this->_item;
        $redeemedPoints = Mage::helper('rewards')
            ->unhashIt($item->getRedeemedPointsHash());
        $redeemedPointsData = array();

        foreach ($redeemedPoints as $point) {
            if (!$point) {
                continue;
            }

            $point = (array)$point;
            $rule = Mage::getModel('rewards/catalogrule_rule')->load($point['rule_id']);
            
            if (!$rule->getId()) {
                continue;
            }

            $pointsAmt = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT];
            $itemHasRedemptions = true;
            $pointsQty = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT]
                * $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY];

            $discount = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_EFFECT];
            $pointsApplicQty = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY];

            $baseItemPrice = $item->getBaseCalculationPrice();
            if (
                Mage::helper('tax')->priceIncludesTax()
                && (
                    Mage::helper('tax')->displayPriceIncludingTax()
                    || Mage::helper('tax')->displayBothPrices()
                )
            ) {
                $baseItemPrice *= (1 + $item->getTaxPercent() / 100);
            }

            $adjustedPrice = Mage::helper('rewards')
                ->priceAdjuster($baseItemPrice, $discount, false);

            if ($adjustedPrice < 0) {
                $adjustedPrice = 0;
            }

            $discount = ($baseItemPrice - $adjustedPrice) * $pointsApplicQty;
            $discount = $this->_getAggregatedCart()->getStore()->convertPrice($discount,false);
            $discountVal = $discount;
            $discount = $this->_getAggregatedCart()->getStore()->formatPrice($discount, false);

            $ruleId = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID];
            $instId = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_INST_ID];
            $currencyId = $point[TBT_Rewards_Model_Catalogrule_Rule::POINTS_CURRENCY_ID];
            $pointsStr = Mage::getModel('rewards/points')->set($currencyId, $pointsQty);
            $unitPointsStr = Mage::getModel('rewards/points')->set($currencyId, $pointsAmt);

            $redeemedPointsData[] = array(
                'currency_id'     => $currencyId,
                'points'          => array($currencyId => $pointsQty),
                'points_str'      => $pointsStr,
                'discount'        => $discount,
                'discount_val'    => $discountVal,
                'rule'            => $rule,
                'instance_id'     => $instId,
                'unit_points_str' => $unitPointsStr
            );
        }

        $this->_catalogRedemptionData = $redeemedPointsData;
        
        return $this->_catalogRedemptionData;
    }

    /**
     * Row Total Catalog Discount Amount
     * @return float
     */
    public function getCatalogRedemptionRowAmount()
    {
        $sum = 0;

        foreach ($this->getCatalogRedemptionData() as $redemptionRowData) {
            $sum += $redemptionRowData['discount_val'];
        }

        return $sum;
    }

    /**
     * Row Subtotal Including Tax
     * @return string
     */
    public function getRowSubtotalInclTax()
    {
        $rowSubtotal = Mage::helper('checkout')->getBaseSubtotalInclTax($this->getItem())
            - $this->getItem()->getBaseDiscountTaxCompensation()
            - $this->getItem()->getBaseDiscountAmount();

        $rowSubtotal = $this->_getAggregatedCart()->getStore()->convertPrice($rowSubtotal);

        return Mage::helper('core')->formatPrice($rowSubtotal, false);
    }

    /**
     * Row Subtotal Excluding Tax
     * @return float
     */
    public function getRowSubtotalExclTax()
    {
        $rowSubtotal = Mage::helper('checkout')->getBaseSubtotalInclTax($this->getItem())
            - $this->getItem()->getBaseDiscountTaxCompensation()
            - $this->getItem()->getBaseDiscountAmount();
        $rowSubtotal -= $this->getItem()->getBaseTaxAmount();
        $rowSubtotal = $this->_getAggregatedCart()->getStore()->convertPrice($rowSubtotal);

        return Mage::helper('core')->formatPrice($rowSubtotal, false);
    }

    /**
     * Cart Display Type
     * @return int
     */
    public function getCartDisplayType()
    {
        $store = $this->_getAggregatedCart()->getStore();

        $type = 0;

        if (Mage::helper('tax')->displayCartPriceExclTax($store)) {
            $type = 0;
        } elseif (Mage::helper('tax')->displayCartPriceInclTax($store)) {
            $type = 1;
        }

        return (Mage::helper('tax')->displayCartBothPrices($store)) ? 2 : $type;
    }
    
    /**
     * Check if cart item has earnings
     * @return boolean
     */
    public function hasEarnings()
    {
        $hasEarned = (sizeof($this->getEarnedPoints()) > 0);
        return $hasEarned;
    }
    
    /**
     * Earnings applied for cart item
     * @return array
     */
    public function getEarnedPoints()
    {
        if (!$this->_earnedPoints) {
            $this->_earnedPoints = Mage::helper('rewards/transfer')
                ->getEarnedPointsOnItem($this->_item);
        }

        return $this->_earnedPoints;
    }

    /**
     * Compute earning data for cart item
     * @return array
     */
    public function getEarningData()
    {
        $earnedPoints = $this->getEarnedPoints();
        $earnedPointsData = array();

        // We do this instead of just using the pointsString function becasue we want
        // each currency to appear on a seperate line.
        foreach ($earnedPoints as $cid => $pointsQty) {
            $earnedPointsStr = (string)Mage::getModel('rewards/points')
                ->set(array($cid => $pointsQty));
            $earnedPointsData[] = $earnedPointsStr;
        }

        return $earnedPointsData;
    }
    
    /**
     * Getter for Current Cart Item
     * @return Mage_Sales_Model_Quote_Item
     */
    public function getItem()
    {
        return $this->_item;
    }
    
    /**
     * This method is overriden in RewardsOnly modue
     * @return boolean
     */
    public function isPointsOnlyItem()
    {
        return false;
    }
    
    /**
     * Aggregation Cart instance
     * @return TBT_Rewards_Model_Sales_Aggregated_Cart
     */
    protected function _getAggregatedCart()
    {
        return Mage::getSingleton('rewards/sales_aggregated_cart');
    }
}