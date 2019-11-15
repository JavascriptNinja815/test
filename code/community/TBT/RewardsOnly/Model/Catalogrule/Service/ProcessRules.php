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
 * This class is used as a service to process rewards only catalog ruless
 * @package     TBT_RewardsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsOnly_Model_Catalogrule_Service_ProcessRules
    extends TBT_Rewards_Model_Catalogrule_Service_ProcessRules
{
    /**
     * Retenders the item's redemption rules and final row total and returns it.
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array a map of the new item redemption data: 
     * array('redemptions_data'=>{...}, 'row_total'=>float)
     */
    protected function _updateRedemptionData($item, $doInclTax = true)
    {
        $redeemedPoints = Mage::helper('rewards')
            ->unhashIt($item->getRedeemedPointsHash());
        
        // Loop through and force applicable qty == item qty for points-only products
        foreach ($redeemedPoints as $key => &$redemptionInstance) {
            $redemptionInstance = (array)$redemptionInstance;
            $ruleId = $redemptionInstance[self::POINTS_RULE_ID];
            $rule = Mage::helper('rewards/rule')->getCatalogRule($ruleId);
            
            if($this->_isOneRedemptionMode() && $rule->getPointsOnlyMode()) { 
                // The total quantity that the redemption instance should apply to should be equal
                // to the total quantity in the item
                $redemptionInstance[self::POINTS_APPLICABLE_QTY] = $item->getQty();
            }
        }
        
        $item->setRedeemedPointsHash(Mage::helper('rewards')->hashIt($redeemedPoints));

        return parent::_updateRedemptionData($item, $doInclTax);
    }
    
    /**
     * Is only one redemption valid to be applied
     * @return boolean
     */
    protected function _isOneRedemptionMode()
    {
    	$pointsAsPrice =  Mage::helper('rewardsonly/config')->showPointsAsPrice();
    	$oneRedemptionOnly = Mage::helper('rewardsonly/config')->forceOneRedemption();
    	$forceRedemptions = Mage::helper('rewardsonly/config')->forceRedemptions();
    	return ($pointsAsPrice && $oneRedemptionOnly && $forceRedemptions);
    }
}