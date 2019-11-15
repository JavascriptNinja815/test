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
 * Checkout Cart Item PointsOnly Renderer
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer
    extends Mage_Checkout_Block_Cart_Item_Renderer
{
    /**
     * Show Points Column
     * @return boolean
     */
    public function showPointsColumn()
    {
        return Mage::helper('rewards/cart')->showPointsColumn();
    }

    /**
     * Show Before Points Column
     * @return boolean
     */
    public function showBeforePointsColumn()
    {
        return Mage::helper('rewards/cart')->showBeforePointsColumn();
    }

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
        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData) || $this->getItem()->getRowTotal() > 0.00001) {
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
        $pointsData = $this->_getItemPointsCost();
        
        if (empty($pointsData)) {
            $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
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
        $pointsData = $this->_getItemPointsCost();

        if (empty($pointsData)) {
            $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            return Mage::helper('rewards')->getPointsString(array($currencyId => 0));
        }

        return $pointsData['points_unit_price_string'];
    }

    /**
     * Get Item PointsOnly Data
     * @return array
     */
    private function _getItemPointsCost()
    {
        return Mage::getSingleton('rewardspointsonly/service_processRules')->getItemPointsCost($this->getItem());
    }
}
