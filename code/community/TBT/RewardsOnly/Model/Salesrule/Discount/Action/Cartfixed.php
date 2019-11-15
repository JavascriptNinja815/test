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
 * Shopping Cart Rule Validator
 *
 * @category   TBT
 * @package    TBT_RewardsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsOnly_Model_Salesrule_Discount_Action_Cartfixed extends TBT_Rewards_Model_Salesrule_Discount_Action_Cartfixed 
{
    /**
     * Calculate quote total for rule and save results
     *
     * @param mixed $items
     * @param TBT_Rewards_Model_Salesrule_Rule $rule
     * @return TBT_Rewards_Model_Salesrule_Discount_Action_Cartfixed
     * @see TBT_Rewards_Model_Salesrule_Discount_Action_Cartfixed::initRewardsTotals()
     */
    protected function initRewardsTotals($items, $rule) 
    {
        if (!$items) {
            return $this;
        }

        $ruleTotalItemsPrice = 0;
        $ruleTotalBaseItemsPrice = 0;
        $validItemsCount = 0;

        foreach ($items as $item) {
            //Skipping child items to avoid double calculations
            if ($item->getParentItemId()) {
                continue;
            }
            if (!$rule->getActions()->validate($item)) {
                continue;
            }
            if (Mage::helper('rewardsonly')->isPointsOnly($item)) {
                continue;
            }

            $qty = $this->_getItemQty($item, $rule);

            $ruleTotalItemsPrice += $this->_getItemPrice($item) * $qty;
            $ruleTotalBaseItemsPrice += $this->_getItemBasePrice($item) * $qty;

            $validItemsCount++;
        }

        $this->_rewardsRulesItemTotals[$rule->getId()] = array(
            'items_price' => $ruleTotalItemsPrice,
            'base_items_price' => $ruleTotalBaseItemsPrice,
            'items_count' => $validItemsCount,
        );
        
        return $this;
    }
}
