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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping Cart Rule Validator
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Salesrule_Discount_Action_Bypercent extends TBT_Rewards_Model_Salesrule_Discount_Action_Abstract {

	
	public function applyDiscounts(&$cartRules, $address, $item, $rule, $qty) {
		
		list ( $discountAmount, $baseDiscountAmount ) = $this->calcItemDiscount ( $item, $address, $rule, $cartRules, $qty );
	
		//@nelkaake -a 11/03/11: Save our discount due to spending points
		if ($rule->getPointsAction () == TBT_Rewards_Model_Salesrule_Actions::ACTION_DISCOUNT_BY_POINTS_SPENT) {
			$new_total_rsd = (float)$address->getTotalBaseRewardsSpendingDiscount();
			$new_total_rsd = $new_total_rsd + $baseDiscountAmount;
			$address->setTotalBaseRewardsSpendingDiscount($new_total_rsd);
		}

        if ($rule->isRedemptionRule()) {
            $address->getQuote()->appendRewardsCartDiscountAmounts($item, $rule, $baseDiscountAmount, $discountAmount);
        }

		return array ( $discountAmount, $baseDiscountAmount );
	}
	
	
	public function calcItemDiscount($item, $address, $rule, $qty = null){
		return $this->_getTotalPercentDiscountOnitem($item, $address, $rule, $qty);
	}
	public function calcCartDiscount($item, $address, $rule, &$cartRules, $qty = null) {
		return $this->_getTotalPercentDiscount($item, $address, $rule, $cartRules, $qty);
	}
		
	/**
	 * Returns a total discount on the cart from the provided items
	 *
	 * @param Mage_Sales_Model_Quote_Item $item
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param TBT_Rewards_Model_Sales_Rule $rule
	 * @param int $qty	max discount qty or unlimited if null
	 * @return float
	 */
	protected function _getTotalPercentDiscount($item, $address, $rule, $qty = null) {
		$all_items = $address->getAllItems ();
		$store = $item->getQuote ()->getStore ();
		
        $totalDiscountOnCart = 0;
        
        $discountPercent = $this->_getRulePercent($rule, $item->getQuote());
        
        // TODO move this to a method
		if ($rule->getPointsAction () != TBT_Rewards_Model_Salesrule_Actions::ACTION_DISCOUNT_BY_POINTS_SPENT) {
			foreach ( $all_items as $cartItem ) {
				if (! $rule->getActions ()->validate ( $cartItem )) {
					continue;
				}
				
				list($item_row_total, $item_base_row_total) = $this->_getDiscountableRowTotal($address, $item, $rule);	
				$discountOnItem = $item_row_total * $discountPercent;
				$totalDiscountOnCart += $discountOnItem;
			}
			return $totalDiscountOnCart;
		} 

        $totalAmountToDiscount = 0;
		foreach ( $all_items as $cartItem ) {
            if (! $rule->getActions ()->validate ( $cartItem )) {
                    continue;
            }
            list($item_row_total, $item_base_row_total) = $this->_getDiscountableRowTotal($address, $item, $rule);	
            $totalAmountToDiscount += $item_row_total; // $cartItem->getTaxAmount();
        }

        // @nelkaake -a 16/11/10: 
        if ($rule->getApplyToShipping ()) {
                $totalAmountToDiscount += $address->getBaseShippingAmountForDiscount();
        }

        $totalDiscountOnCart = $totalAmountToDiscount * $discountPercent;
		
		return $totalDiscountOnCart;
	}

    /**
     * Get the discount percentage from the rule with consideration for the session points spending amount
     * @param TBT_Rewards_Model_Salesrule $rule
     */
    protected function _getRulePercent($rule, $quote) {
        
        $discountPercent = 0;
        
        if ( $rule->getPointsAction() == TBT_Rewards_Model_Salesrule_Actions::ACTION_DISCOUNT_BY_POINTS_SPENT ) {
            $points_spent = $quote->getPointsSpending();
            $discountPercent = (($rule->getPointsDiscountAmount() * floor(($points_spent / $rule->getPointsAmount()))) / 100);
        } else {
            $discountPercent = (float) $rule->getPointsDiscountAmount() / 100;
        }
        
        $discountPercent = min($discountPercent, 1);
        
        return $discountPercent;
    }
	
	protected function _getTotalSpendingPercent($rule, $quote) {
	
	    if ( $rule->getPointsAction() == TBT_Rewards_Model_Salesrule_Actions::ACTION_DISCOUNT_BY_POINTS_SPENT ) {
	        $points_spent = $quote->getPointsSpending();
	        $multiplier = floor(($points_spent / $rule->getPointsAmount()));
	    } else { 
	    	$multiplier = 1; 
	    }
		$discountPercent = (($rule->getPointsDiscountAmount () * $multiplier ) / 100);
	    
		$discountPercent = min ( $discountPercent, 1 );
		
		return $discountPercent;
	}
	

	protected function reverseShippingDiscountAmount($address, $rule) {
		$discountPercent = $this->_getTotalSpendingPercent($rule, $address->getQuote());
		
		return $discountPercent;
	}
	
	/**
	 * Returns a total discount on the cart from the provided items
	 *
	 * @param Mage_Sales_Model_Quote_Item $item
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param TBT_Rewards_Model_Sales_Rule $rule
	 * @param array() &$cartRules
	 * @param int $qty	max discount qty or unlimited if null
	 * @return array($discountAmount, $baseDiscountAmount)
	 */
	protected function _getTotalPercentDiscountOnitem($item, $address, $rule, &$cartRules, $qty = null) {
		$quote = $item->getQuote ();
		$store = $item->getQuote ()->getStore ();
		
        $rulePercent = $this->_getRulePercent($rule, $quote);

		list($item_row_total, $item_base_row_total) = $this->_getDiscountableRowTotal($address, $item, $rule);
		
		$discountAmount = ($item_row_total ) * $rulePercent;
		$baseDiscountAmount = ($item_base_row_total) * $rulePercent;
		
		return array ($discountAmount, $baseDiscountAmount );
	}
	

}
