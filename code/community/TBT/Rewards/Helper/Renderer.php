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
class TBT_Rewards_Helper_Renderer extends Mage_Core_Helper_Abstract
{
    protected $_redeemedPoints  = array();
    protected $_redemptionsData = array();

    /**
     * Checks if this sales item was bought with points-only.
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return bool     Returns true if points-only product, false otherwise.
     */
    public function getIsPointsOnly($item)
    {
        $item = $this->_getOrderItem($item);
        if (!$this->hasRedemptions($item)) {
            return false;
        }

        // TODO: better way ?!?
        if ((int)$item->getRowTotalAfterRedemptions() != 0 || (int)$item->getRowTotalAfterRedemptionsInclTax() != 0) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves the price of this item in points.
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return string
     */
    public function getItemPointsPrice($item)
    {
        $item            = $this->_getOrderItem($item);
        $redemptionsData = $this->getRedemptionsData($item);
        if (empty($redemptionsData) || !$redemptionsData['item_points_price']) {
            return '';
        }

        return $redemptionsData['item_points_price'];
    }

    /**
     * Retrieves the subtotal of this item in points
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return string
     */
    public function getItemSubtotalPointsPrice($item)
    {
        $item            = $this->_getOrderItem($item);
        $redemptionsData = $this->getRedemptionsData($item);
        if (empty($redemptionsData) || !$redemptionsData['item_subtotal_points_price']) {
            return '';
        }

        return $redemptionsData['item_subtotal_points_price'];
    }

    public function getRedeemedPoints($item)
    {
        $item = $this->_getOrderItem($item);
        if (isset($this->_redeemedPoints[$item->getId()])) {
            return $this->_redeemedPoints[$item->getId()];
        }

        $this->_redeemedPoints[$item->getId()] = Mage::helper('rewards')->unhashIt($item->getRedeemedPointsHash());

        return $this->_redeemedPoints[$item->getId()];
    }

    /**
     * Checks if there are redemptions on this item
     * @param  Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return bool
     */
    public function hasRedemptions($item)
    {
        $item = $this->_getOrderItem($item);
        $hasRedeemed = (sizeof($this->getRedeemedPoints($item)) > 0);
        return $hasRedeemed;
    }

    public function getRedemptionsData($item)
    {
        $item = $this->_getOrderItem($item);
        if (isset($this->_redemptionsData[$item->getId()])) {
            return $this->_redemptionsData[$item->getId()];
        }

        $this->_redemptionsData[$item->getId()] = array();

        $redeemedPoints = $this->getRedeemedPoints($item);
        foreach ($redeemedPoints as $redeemedPoint) {
            $redeemedPoint = (array)$redeemedPoint;

            $currency_id    = $redeemedPoint[TBT_Rewards_Model_Salesrule_Rule::POINTS_CURRENCY_ID];
            $applicable_qty = $redeemedPoint[TBT_Rewards_Model_Salesrule_Rule::POINTS_APPLICABLE_QTY];
            $qty            = $this->_getQty($item);

            $itemPointsAmount         = $redeemedPoint[TBT_Rewards_Model_Salesrule_Rule::POINTS_AMT];
            $itemSubtotalPointsAmount = $redeemedPoint[TBT_Rewards_Model_Salesrule_Rule::POINTS_AMT] * $qty;

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
    protected function _getOrderItem($item)
    {
        $orderItem = $item;
        if ($orderItem instanceof Mage_Sales_Model_Order_Creditmemo_Item || $orderItem instanceof Mage_Sales_Model_Order_Invoice_Item) {
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
    protected function _getQty($item)
    {
        $qty  = (int)$item->getQty();
        if ($item instanceof Mage_Sales_Model_Order_Item) {
            $qty = (int)$item->getQtyOrdered();
        }

        return $qty;
    }
}
