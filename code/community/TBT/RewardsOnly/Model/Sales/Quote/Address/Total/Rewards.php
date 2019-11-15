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
 * @package    [TBT_RewardsOnly]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Quote Address Total Rewards
 *
 * @category   TBT
 * @package    TBT_RewardsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsOnly_Model_Sales_Quote_Address_Total_Rewards extends TBT_Rewards_Model_Sales_Quote_Address_Total_Rewards
{
    /**
     * This triggers right after the subtotal is calculated
     * @see TBT_Rewards_Model_Sales_Quote_Address_Total_Rewards::collect()
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $helper = Mage::helper('rewardsonly');
        
        foreach ($address->getAllItems() as $item) {
            if ($helper->isPointsOnly($item)) {
                $product = $item->getProduct();
                $item->setCustomPrice(0);
                $item->setOriginalCustomPrice(0);
                $product->setIsSuperMode(true);
            }
        }

        parent::collect($address);
        
        /**
         * Set points-only prices to 0. 
         * 
         * This is a fix for a rounding issue that happens because the tax remainder
         * of points-only items is added to the other items.
         *
         * It also subtracts the points only discount from the subtotal and
         * item discount rows
         */
        foreach ($address->getAllItems() as $item) {
            if ($helper->isPointsOnly($item)) {
                $product = $item->getProduct();
                $productPrice = $product->getFinalPrice();
                $product->setInitialPrice($productPrice);
                $product->setPrice(0);
            }
        }
        
        return $this;
    }
}
