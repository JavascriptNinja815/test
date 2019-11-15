<?php

/**
 * Helper Data
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsOnly_Helper_Data extends Mage_Core_Helper_Abstract {

    public function isEnabled() {
        return true;
    }

	/**
	 * 
	 * 
	 * @param TBT_Rewards_Model_Catalog_Product $product
	 * @param $positive : if true, the number will be positive even if the rule is a redemption
	 * @return string
	 */
	public function getPointsString($rule, $product, $positive=true) {	
		$pts = $this->getPointsForProduct($product);
		if($pts < 0 && $positive) {
			$pts = $pts * -1;
		}
		$str = Mage::helper('rewards')->getPointsString(array(
			$rule->getPointsCurrencyId() => $pts
		));
		return $str;
	}
    
    /**
     * Get total price of all points only products
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array(float, float)
     */
    public function getPointsOnlyTotal($address)
    {
        $customer = $address->getCustomer();
        $ruleSelector = Mage::helper('rewardsonly/config')->getRedemptionSelectionAlgorithm();
        
        $total = 0;
        $baseTotal = 0;
        foreach ($address->getAllItems() as $item) {
            $ruleSelector->init($customer, $item->getProduct());
    
            // We check if there is a points-only rule applied to the product
            if ($ruleSelector->hasRule()) {
                if (Mage::helper('tax')->priceIncludesTax()) {
                    $total += $item->getRowTotalInclTax();
                    $baseTotal += $item->getBaseRowTotalInclTax();
                } else {
                    $total += $item->getRowTotal();
                    $baseTotal += $item->getBaseRowTotal();
                }
            }
        }
        
        return array(
            'points_only_discount'      => $total, 
            'base_points_only_discount' => $baseTotal
        );
    }
    
    /**
     * Get total tax compensation for points-only items in the address
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param bool $resetTaxCompensation | will set every points-only item's tax compensation to 0 if true
     * @return float
     */
    public function getPointsOnlyTaxData($address, $resetTaxCompensation = false)
    {
        $customer = $address->getCustomer();
        $ruleSelector = Mage::helper('rewardsonly/config')->getRedemptionSelectionAlgorithm();
        
        $redeemer = Mage::getSingleton('rewards/redeem');
        // Reset rounding deltas
        $redeemer->resetDeltas();
                
        $rewardsDiscountTaxAmount = $discountTaxCompensation = 0;
        foreach ($address->getAllItems() as $item) {
            $ruleSelector->init($customer, $item->getProduct());
    
            // If this is points-only item
            if ($ruleSelector->hasRule()) {
                $discountTaxCompensation += $item->getDiscountTaxCompensation();
                
                if ($resetTaxCompensation) {
                    $item->setDiscountTaxCompensation(0);
                }
                
                $catalogDiscountInclTax = $redeemer->getTotalCatalogDiscount($item);
                $catalogDiscountExclTax = $catalogDiscountInclTax / (1 + $item->getTaxPercent() / 100);
                $rewardsDiscountTaxAmount += $catalogDiscountInclTax - $catalogDiscountExclTax + $address->getRewardsRoundingDelta();
            }
        }
        
        return array(
            'rewards_discount_tax_amount'   => $rewardsDiscountTaxAmount, 
            'discount_tax_compensation'     => $discountTaxCompensation
        );
    }
    
    /**
     * Check if a quote item is points only
     * 
     * @param Mage_Sales_Model_Quote_Item $item
     */
    public function isPointsOnly($item)
    {
        $customer = $item->getQuote()->getCustomer();
        $ruleSelector = Mage::helper('rewardsonly/config')->getRedemptionSelectionAlgorithm();
        
        $ruleSelector->init($customer, $item->getProduct());
        return $ruleSelector->hasRule();
    }
}
