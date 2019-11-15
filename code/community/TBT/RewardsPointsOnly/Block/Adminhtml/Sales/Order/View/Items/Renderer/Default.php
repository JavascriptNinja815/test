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
 * Adminhtml Order View Item PointsOnly Renderer
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Adminhtml_Sales_Order_View_Items_Renderer_Default
    extends Mage_Adminhtml_Block_Sales_Order_View_Items_Renderer_Default
{
    /**
     * Cached Redeemed Points
     * @var array
     */
    protected $_redeemedPoints  = array();

    /**
     * Cached Redemptions Data
     * @var array
     */
    protected $_redemptionsData = array();

    /**
     * Is Item Points Only
     * @return boolean
     */
    public function getIsPointsOnly()
    {
        return $this->hasItemPointsPrice();
    }
    
    /**
     * Checks if Item has Points Price
     * @return boolean
     */
    protected function hasItemPointsPrice()
    {
        if ($this->hasItemOldPointsPrice()) {
            return true;
        }
        
        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if Item has Points Price (compatibility mode with older pointsonly version)
     * @return boolean
     */
    protected function hasItemOldPointsPrice()
    {
        $item = $this->_getOrderItem($this->getItem());

        if (!$this->hasRedemptions()) {
            return false;
        }

        if (
            (float)$item->getRowTotal() !== 0.0
            || (float)$item->getRowTotalInclTax() !== 0.0
        ) {
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
        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        if ($this->hasItemOldPointsPrice()) {
            $redemptionsData = $this->getRedemptionsData();

            if (empty($redemptionsData) || !$redemptionsData['item_points_price']) {
                return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
            }

            return $redemptionsData['item_points_price'];
        }

        $pointsData = $this->_getItemPointsCost();
        
        if (empty($pointsData)) {
            return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
        }

        return $pointsData['points_row_total_price_string'];
    }

    /**
     * Returns the subtotal of the currently rendered product in Points.
     * @return TBT_Rewards_Model_Points
     */
    public function getItemSubtotalPointsPrice()
    {
        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        if ($this->hasItemOldPointsPrice()) {
            $redemptionsData = $this->getRedemptionsData();

            if (empty($redemptionsData) || !$redemptionsData['item_subtotal_points_price']) {
                return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
            }

            return $redemptionsData['item_subtotal_points_price'];
        }

        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData)) {
            return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
        }

        return $pointsData['points_unit_price_string'];
    }

    /**
     * Item Redeemed Points
     * 
     * @return array
     */
    public function getRedeemedPoints()
    {
        $item = $this->getItem();
        $item = $this->_getOrderItem($item);
        
        if (isset($this->_redeemedPoints[$item->getId()])) {
            return $this->_redeemedPoints[$item->getId()];
        }

        $this->_redeemedPoints[$item->getId()] = Mage::helper('rewards')
            ->unhashIt($item->getRedeemedPointsHash());

        return $this->_redeemedPoints[$item->getId()];
    }

    /**
     * Checks if there are redemptions on this item
     *
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return bool
     */
    public function hasRedemptions()
    {
        $item = $this->getItem();
        $item = $this->_getOrderItem($item);

        $hasRedeemed = (sizeof($this->getRedeemedPoints()) > 0);
        
        return $hasRedeemed;
    }

    /**
     * Item Redemptions Data
     * @return array
     */
    public function getRedemptionsData()
    {
        $item = $this->getItem();
        $item = $this->_getOrderItem($item);
        
        if (isset($this->_redemptionsData[$item->getId()])) {
            return $this->_redemptionsData[$item->getId()];
        }

        $this->_redemptionsData[$item->getId()] = array();

        $redeemedPoints = $this->getRedeemedPoints();
        
        foreach ($redeemedPoints as $redeemedPoint) {
            $redeemedPoint = (array)$redeemedPoint;

            $currency_id    = $redeemedPoint['points_currency_id'];
            $applicable_qty = $redeemedPoint['applicable_qty'];
            $qty            = $this->_getQty($item);

            $itemPointsAmount         = $redeemedPoint['points_amt'];
            $itemSubtotalPointsAmount = $redeemedPoint['points_amt'] * $qty;

            $itemPointsPrice = Mage::getModel('rewards/points')->set($currency_id, $itemPointsAmount);
            $itemSubtotalPointsPrice = Mage::getModel('rewards/points')->set($currency_id, $itemSubtotalPointsAmount);

            $this->_redemptionsData[$item->getId()] = array(
                'currency_id'                 => $currency_id,
                'item_points_amount'          => $itemPointsAmount,
                'item_points_price'           => $itemPointsPrice,
                'item_subtotal_points_amount' => $itemSubtotalPointsAmount,
                'item_subtotal_points_price'  => $itemSubtotalPointsPrice,
            );
        }

        return $this->_redemptionsData[$item->getId()];
    }

    /**
     * Retrieves the associated order item
     *
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return Mage_Sales_Model_Order_Item
     */
    private function _getOrderItem($item)
    {
        $orderItem = $item;
        if (
            $orderItem instanceof Mage_Sales_Model_Order_Creditmemo_Item
            || $orderItem instanceof Mage_Sales_Model_Order_Invoice_Item
        ) {
            $orderItem = $orderItem->getOrderItem();
        }

        return $orderItem;
    }

    /**
     * Retrieve the ordered/invoiced qty for this product
     *
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return int
     */
    private function _getQty($item)
    {
        $qty  = (int)$item->getQty();
        if ($item instanceof Mage_Sales_Model_Order_Item) {
            $qty = (int)$item->getQtyOrdered();
        }

        return $qty;
    }

    /**
     * Get Item PointsOnly Data
     * @return array
     */
    private function _getItemPointsCost()
    {
        $item = $this->_getOrderItem($this->getItem());
        $itemQtyOrdered = $this->_getQty($this->getItem());

        return Mage::getSingleton('rewardspointsonly/service_processRules')->getItemPointsCost($item, $itemQtyOrdered);
    }
}
