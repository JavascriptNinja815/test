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
 * Order Email Item Default Renderer
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Sales_Order_Email_Items_Order_Default
    extends Mage_Sales_Block_Order_Email_Items_Order_Default
{
    /**
     * Is Points Only
     * @return boolean
     */
    public function getIsPointsOnly()
    {
        return $this->hasItemPointsPrice();
    }

    /**
     * Has Item Points Only Price
     * @return boolean
     */
    protected function hasItemPointsPrice()
    {
        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the price of the currently rendered product in Points.
     * @return string
     */
    public function getItemPointsPrice()
    {
        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData)) {
            $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
        }

        return $pointsData['points_row_total_price_string'];
    }

    /**
     * Returns the subtotal of the currently rendered product in Points.
     * @return string
     */
    public function getItemSubtotalPointsPrice()
    {
        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData)) {
            $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
        }

        return $pointsData['points_unit_price_string'];
    }

    /**
     * Get Item Points Only Data
     * @return array
     */
    private function _getItemPointsCost()
    {
        $item = $this->getItem();
        $qty = null;

        if ($item instanceof Mage_Sales_Model_Order_Invoice_Item) {
            $orderItem = $item->getOrderItem();
            $qty = $orderItem->getQtyInvoiced();
        } elseif ($item instanceof Mage_Sales_Model_Order_Creditmemo_Item) {
            $orderItem = $item->getOrderItem();
            $qty = $orderItem->getQtyRefunded();
        } else {
            $orderItem = $item;
        }

        return Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getItemPointsCost($orderItem, $qty);
    }

    /**
     * Get Rewards Renderer Helper
     * @return TBT_Rewards_Helper_Renderer
     */
    protected function _getHelper()
    {
        return Mage::helper('rewards/renderer');
    }
}
