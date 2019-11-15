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
 * This class is used as a service to process rewards catalog ruless
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Catalogrule_Service_ProcessRules
    extends Mage_Core_Model_Abstract
{
    /**
     * Identifier for points rule id
     */
    const POINTS_RULE_ID        = TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID;
    
    /**
     * Identifier for points applicable qty
     */
    const POINTS_APPLICABLE_QTY = TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY;
    
    /**
     * Identifier for points effect
     */
    const POINTS_EFFECT         = TBT_Rewards_Model_Catalogrule_Rule::POINTS_EFFECT;
    
    /**
     * Identifier for points uses
     */
    const POINTS_USES           = TBT_Rewards_Model_Catalogrule_Rule::POINTS_USES;
    
    /**
     * Identifier for quote item table
     */
    const SALES_FLAT_QUOTE_ITEM = "sales_flat_quote_item";
    
    /**
     * Identifier for applicable qty
     */
    const APPLICABLE_QTY        = TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY;
    
    /**
     * Identifier for points amt
     */
    const POINTS_AMT            = TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT;
    
    /**
     * Identifier for currency id
     */
    const POINTS_CURRENCY_ID    = TBT_Rewards_Model_Catalogrule_Rule::POINTS_CURRENCY_ID;
    
    /**
     * Identifier for instance id
     */
    const POINTS_INST_ID        = TBT_Rewards_Model_Catalogrule_Rule::POINTS_INST_ID;

    /**
     * Tax calculation model
     *
     * @var Mage_Tax_Model_Calculation
     */
    protected $_taxCalculator = null;

    /**
     * Tax helper
     * @var Mage_Tax_Helper_Data
     */
    protected $_taxHelper = null;

    /**
     * Tax config model
     * @var Mage_Tax_Model_Config
     */
    protected $_taxConfig = null;
    
    /**
     *
     * Tax amount remainders
     * @var array 
     */
    protected $_roundingDeltas = array();
    
    /**
     * Tax adjustments for paypal express
     * 
     * @var array
     */
    protected $_appliedTaxAdjustments = array();
    
    /**
     * If tax adjustments where applied or not
     *
     * @var bool
     */
    protected $_wereAdjustmentsApplied = false;
    
    /**
     * Tax amounts for items already calculated
     *
     * @var array
     */
    protected $_calculatedTaxAmounts = array();
    
    /**
     * Current handle
     *
     * @var string
     */
    protected $_handle;
    
    /**
     * Instance of Cart aggregation model
     * @var TBT_Rewards_Model_Sales_Aggregated_Cart 
     */
    protected $_aggregatedCart;
    
    /**
     * Quote Helper Methods
     * @var TBT_Rewards_Helper_Quote 
     */
    protected $_quoteHelper;
    
    /**
     * Main constructor
     * @return \TBT_Rewards_Model_CatalogRule_Service_ProcessRules
     */
    public function _construct()
    {
        $this->_taxCalculator = Mage::getSingleton('tax/calculation');
        $this->_taxHelper     = Mage::helper('tax');
        $this->_taxConfig     = Mage::getSingleton('tax/config');
        $this->_handle        = Mage::app()->getRequest()->getRouteName() 
                                . '_' 
                                . Mage::app()->getRequest()->getControllerName()
                                . '_'
                                . Mage::app()->getRequest()->getActionName();
        $this->_aggregatedCart = Mage::getSingleton('rewards/sales_aggregated_cart');
        $this->_quoteHelper    = Mage::helper('rewards/quote');

        return $this;
    }
    
    /**
     * Writes the specified redemption usage data to the specified item, provided that the redemption
     * is valid for the item.  If this rule has already been saved to the item, it is overwritten.
     * Assumes the product has already been added to the cart.
     * @param Mage_Catalog_Model_Product $product
     * @param int $apply_rule_id
     * @param int $apply_rule_uses
     * @param int $qty
     * @param Mage_Sales_Model_Quote_Item $item
     */
    public function writePointsToQuote($product, $applyRuleId, $applyRuleUses, $qty, $item = null)
    {
        if (!$product) {
            return $this;
        }
        
        $product = $this->_ensureProduct($product);

        if (!$applyRuleId) {
            return $this;
        }

        if ($applyRuleUses < 0) {
            throw new Exception(
                Mage::helper('rewards')->__(
                    "You can't spend a negative amount of points."
                )
            );
        }

        if (!$applyRuleUses) {
            $applyRuleUses = 0;
        }

        if (empty($qty)) {
            $qty = 1;
        }

        if (!$item) {
            $item = $this->_aggregatedCart->getQuote()->getItemByProduct($product);
            if (!$item) {
                return $this;
            }
        }

        if (empty($applyRuleId) && $applyRuleId != '0') {
            // No new rule applied, so no need to adjust redeemed points set.
            $this->refactorRedemptions($item);
            return $this;
        }

        $this->_updateRedeemedPointsHash(
            Mage::helper('rewards')->now(),
            $this->_aggregatedCart->getWebsiteId(),
            $this->_aggregatedCart->getCustomerGroupId(),
            $product->getId(),
            $item,
            $applyRuleId, $qty, true, $applyRuleUses, true
        );

        $this->refactorRedemptions($item, ($item->getId() ? true : false));

        return $this;
    }
    
    /**
     * Retenders the items listed in the item list
     *
     * @param array(Mage_Sales_Model_Quote_Item) $items
     */
    public function refactorRedemptions($items, $doSave = true)
    {
        if (!is_array($items)) {
            $items = array($items);
        }

        foreach ($items as $item) {
            $this->_refactorRedemption($item, $doSave);
        }

        return $this;
    }

    /**
     * Retenders the item's redemption rules and final row total
     *
     * @param Mage_Sales_Model_Quote_Item $persistentItem
     */
    protected function _refactorRedemption(&$persistentItem, $doSave = true)
    {
        // clone the item so any changes we make don't persist unless we want them to (ie: doSave)
        $item = $this->_quoteHelper->cloneQuoteItem($persistentItem);

        // Write to the database the new item row information
        $r               = $this->_updateRedemptionData($item);
        $rowTotal        = $r['row_total'];
        $rowTotalInclTax = $r['row_total_incl_tax'];
        $redems = $r['redemptions_data'];
        
        if ($rowTotal < 0) {
            $rowTotal = 0;
        }
        
        if ($rowTotalInclTax < 0) {
            $rowTotalInclTax = 0;
        }

        $this->_resetItemDiscounts($item);

        $item->setRowTotal($rowTotal);
        $item->setRowTotalInclTax($rowTotalInclTax);

        $baseRowTotal = $item->getQuote()->getStore()->roundPrice(
            Mage::helper('rewards/price')->getBaseCurrencyDelta($item->getBaseRowTotal(), $item->getQuote())
            + Mage::helper('rewards/price')->getReversedCurrencyPrice($rowTotal, null, false)
        );

        $baseRowTotalInclTax = $item->getQuote()->getStore()->roundPrice(
            Mage::helper('rewards/price')->getBaseCurrencyDelta($item->getBaseRowTotalInclTax(), $item->getQuote())
            + Mage::helper('rewards/price')->getReversedCurrencyPrice($rowTotalInclTax, null, false)
        );

        if ($baseRowTotal - $item->getBaseRowTotal() < 0.000001) {
            $item->setBaseRowTotal($baseRowTotal);
        }

        if ($baseRowTotalInclTax - $item->getBaseRowTotalInclTax() < 0.000001) {
            $item->setBaseRowTotalInclTax($baseRowTotalInclTax);
        }

        $regularDiscount = $item->getBaseDiscountAmount();
        if (empty($regularDiscount)) {
            $item->setRowTotalWithDiscount($item->getRowTotal());
            $item->setBaseRowTotalWithDiscount($item->getBaseRowTotal());
        }
        
        if (!Mage::app()->getStore()->isAdmin()) {
            $this->_applyPayPalExpressTaxAdjustments($item);
        }
        
        $this->_calcTaxAmounts($item);
        
        $persistentItem->setData($item->getData());
        
        if ($doSave) {
            $persistentItem->save();
        }

        return $this;
    }
    
    /**
     * Make paypal express work
     * 
     * @param Mage_Sales_Model_Quote_Item $item
     */
    protected function _applyPayPalExpressTaxAdjustments($item)
    {
        // Apply tax adjustments if are paying with paypal express
        if (($this->_handle == 'paypal_express_start' || $this->_handle == 'paypal_express_placeOrder')
                && !$this->_wereAdjustmentsApplied 
                && !empty($this->_appliedTaxAdjustments)) {
            $quote = $item->getQuote();
            $rewardsDiscount = $quote->getRewardsDiscountAmount();

            // Fetch the correct address
            $address = ($quote->getIsVirtual()) ? $quote->getBillingAddress() : $quote->getShippingAddress();
            // Convert tax amount from string to float so it's not set to 0
            $originalTax = (float) $address->getBaseTaxAmount();
            $address->setBaseTaxAmount($originalTax);

            if (Mage::getSingleton('rewards/session')->areAnyPointsSpentOnShoppingCartRules()) {
                // Shopping cart rule adjustments (when catalog rules are applied as well)
                $hiddenTaxAmount = $quote->getRewardsDiscountTaxAmount();
                $address->addBaseTotalAmount('tax', -$hiddenTaxAmount);
                $rewardsDiscount -= $hiddenTaxAmount;
            } else {
                // Catalog Rule Adjustments
                foreach ($this->_appliedTaxAdjustments as $adjustment) {
                    $address->addBaseTotalAmount('tax', -$adjustment);
                    $rewardsDiscount -= $adjustment;
                }
            }
            
            $quote->setRewardsDiscountAmount($rewardsDiscount);
            $this->_wereAdjustmentsApplied = true;
        }
    }
    
    /**
     * Renders the item's redemption rules and final row total and returns it.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     *
     * @return array a map of the new item redemption data:
     * array('redemptions_data'=>{...}, 'row_total'=>float)
     */
    protected function _updateRedemptionData($item, $doInclTax = true)
    {
        // Step 1: Create a map of usability for all applied redemptions
        //echo "$item->getRedeemedPointsHash()";
        $redeemedPoints = Mage::helper('rewards')
            ->unhashIt($item->getRedeemedPointsHash());

        // Prepare data from item and initalize counters
        if ($item->getQuote()) {
            $storeCurrency = round($item->getQuote()->getStoreToQuoteRate(), 4);
        }
        if ($item->getOrder()) {
            $storeCurrency = round($item->getOrder()->getStoreToQuoteRate(), 4);
        }

        if ($item->hasCustomPrice()) {
            $productPrice = (float) $item->getCustomPrice() * $storeCurrency;
        } else {
            if ($this->_taxHelper->priceIncludesTax() && $item->getPriceInclTax()) {
                $productPrice = $item->getPriceInclTax() / (1 + $item->getTaxPercent() / 100) * $storeCurrency;
            } else {
                $productPrice = (float) $item->getPrice() * $storeCurrency;
            }
        }
        
        if ($item->getParentItem() || sizeof($redeemedPoints) == 0) {
            return array(
                'redemptions_data'   => array(),
                'row_total_incl_tax' => $item->getRowTotalInclTax(),
                'row_total'          => $item->getRowTotal()
            );
        }

        // make sure we fetch the total_qty based on whether this is a Quote or Order item
        $quoteItem = $item instanceof Mage_Sales_Model_Quote_Item;
        $totalQty = $quoteItem ? $item->getQty() : $item->getQtyOrdered();
        $totalQtyRedeemed  = 0.0000;
        $rowTotal = 0.0000;
        $newRedeemedPoints = array();
        $ret = array();

        // Loop through and apply all our rules.
        foreach ($redeemedPoints as $key => &$redemptionInstance) {
            $redemptionInstance = (array) $redemptionInstance;
            $applicQty = $redemptionInstance[self::POINTS_APPLICABLE_QTY];
            $ruleId  = $redemptionInstance[self::POINTS_RULE_ID];
            $effect = $redemptionInstance[self::POINTS_EFFECT];
            $uses = isset($redemptionInstance[self::POINTS_USES]) ? (int) $redemptionInstance[self::POINTS_USES] : 1;
            $rule = Mage::helper('rewards/rule')->getCatalogRule($ruleId);

            // If a rule was turned off at some point in the back-end it should be removed and not calculated in the cart anymore.
            if (!$rule || !$rule->getIsActive()) {
                $this->_removeCatalogRedemptionsFromItem($item, array($ruleId));
                $effect = "";
            }

            $totalQtyRemain = $totalQty - $totalQtyRedeemed;
            if ($totalQtyRemain > 0) {
                if ($totalQtyRemain < $applicQty) {
                    $applicQty = $total_qty_remain;
                    $redemptionInstance[self::POINTS_APPLICABLE_QTY] = $applicQty;
                }

                $priceAfterRedem = $this->_getPriceAfterEffect($productPrice, $effect, $item);

                $rowTotal += $applicQty * (float) $priceAfterRedem;
                $totalQtyRedeemed += $applicQty;
                $newRedeemedPoints[] = $redemptionInstance;
            } else {
                $redemptionInstance[self::POINTS_APPLICABLE_QTY] = 0;
                $redemptionInstance[self::POINTS_USES] = 1; // used once by default
                unset($redeemedPoints[$key]);
            }
        }

        $ret['redemptions_data'] = $newRedeemedPoints;

        // Add in the left over products that perhaps weren't affected by qty adjustment.
        $totalQtyRemain = ($totalQty - $totalQtyRedeemed);
        
        if ($totalQtyRemain < 0) {
            $totalQtyRemain   = 0;
            $totalQtyRedeemed = $totalQty;
        }
        
        $rowTotal += $totalQtyRemain * (float) $productPrice;
        $rowTotalInclTax = $rowTotal * (1 + $item->getTaxPercent() / 100);

        // based on whether prices include/exclude tax we need to round the row_total or row_total_incl_tax
        if ($this->_taxHelper->priceIncludesTax()) {
            $rowTotal = $this->_deltaRound($rowTotal, 'default', $doInclTax, 'row_total');
            // Only add tax adjustments if we are checking out with paypal express
            if ($this->_handle == 'paypal_express_start' || $this->_handle == 'paypal_express_placeOrder') {
                // bundle & grouped product adjustments
                if ($item->getHasChildren() && !$item->getProduct()->isConfigurable()) {
                    $taxAdjustment = $item->getBaseRowTax();
                    if (!$taxAdjustment) {
                        $taxAdjustment = $item->getBaseTaxAmount();
                    }
                    $rowTotal -= $taxAdjustment;
                // configurable, simple & downloadble products adjustment
                } elseif (!$item->getParentItem()) {
                    $taxAdjustment = $item->getTaxAmount() - ($item->getRowTotalAfterRedemptionsInclTax() - $item->getRowTotalAfterRedemptions());
                    $this->_appliedTaxAdjustments[$item->getId()] = $taxAdjustment;
                }
            }
        } else {
            $rowTotalInclTax = $this->_deltaRound($rowTotalInclTax, 'default', $doInclTax, 'row_total');
        }

        $ret['row_total']          = $rowTotal;
        $ret['row_total_incl_tax'] = $rowTotalInclTax;

        return $ret;
;    }

    /**
     * Removes all applicable rules to the item's rule hash.
     * Returns false if no changes were made.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @param array $ruleIdList
     * @param integer $instId redemption instance id (this comes out of the item redemptions hash)
     *
     * @return boolean
     */
    protected function _removeCatalogRedemptionsFromItem(&$item, $ruleIdList, $instId = 0)
    {
        //Check to make sure we can load the redeem points hash alright
        if (!$item->getRedeemedPointsHash()) {
            throw new Exception (
                Mage::helper('rewards')->__("Unable to load the redeem points hash")
            );
        }
        $catalogRedemptions = Mage::helper('rewards')->unhashIt($item->getRedeemedPointsHash());
        
        foreach ($catalogRedemptions as $key => $redemption) {
            $catalogRedemptions [$key] = (array)$redemption;
        }

        $doSave = false;

        foreach ($ruleIdList as $ruleId) {
            $rule = Mage::getModel('rewards/catalogrule_rule')->load($ruleId);
            $foundRuleIdIndex = false;
            
            foreach ($catalogRedemptions as $index => $redemption) {
                $ruleIdIsSame = ($redemption[TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID] == $ruleId);
                $instIdIsSame = (($instId == 0) ? true : ($redemption[TBT_Rewards_Model_Catalogrule_Rule::POINTS_INST_ID] == $inst_id));
                
                if ($ruleIdIsSame && $instIdIsSame) {
                    $foundRuleIdIndex = $index;
                }
            }

            if ($foundRuleIdIndex === false) {
                throw new Exception(
                    "The rule entitled '" . $rule->getName() . "' is not applied to this product."
                );
            } else {
                unset($catalogRedemptions[$foundRuleIdIndex]);
                $item->setRedeemedPointsHash(Mage::helper('rewards')->hashIt($catalogRedemptions));
                $doSave = true;
            }
        }

        if ($doSave) {
            $item->save();
            return true;
        }

        return false;
    }
    
    /**
     * Returns a product price after the given effect has occured.
     * @see also TBT_Rewards_Helper_Data::priceAdjuster
     *
     * @param decimal                     $productPrice
     * @param mixed                       $effect
     * @param Mage_Sales_Model_Quote_Item $item
     * @param boolean                     $calcInclTaxIfApplic if applicable, should I calculate the price including tax amount?
     */
    protected function _getPriceAfterEffect($productPrice, $effect, $item, $calcInclTaxIfApplic = true)
    {
        if ($this->_taxHelper->priceIncludesTax() && $calcInclTaxIfApplic) {
            $productPrice = $productPrice * (1 + $item->getTaxPercent() / 100);
        }

        $priceAfterRedem = Mage::helper('rewards')->priceAdjuster($productPrice, $effect);

        if ($this->_taxHelper->priceIncludesTax() && $calcInclTaxIfApplic) {
            $priceAfterRedem = $priceAfterRedem / (1 + $item->getTaxPercent() / 100);
        }

        return $priceAfterRedem;
    }
    
    /**
     * Round price based on previous rounding operation delta
     *
     * @param float $price
     * @param string $rate
     * @param bool $direction price including or excluding tax
     * @param string $type
     * @return float
     */
    protected function _deltaRound($price, $rate, $direction, $type = 'regular')
    {
        if ($price) {
            $rate = (string)$rate;
            $type = $type . $direction;
            // initialize the delta to a small number to avoid non-deterministic behavior with rounding of 0.5
            $delta = isset($this->_roundingDeltas[$type][$rate]) ? $this->_roundingDeltas[$type][$rate] : 0.000001;
            $price += $delta;
            $this->_roundingDeltas[$type][$rate] = $price - $this->_taxCalculator->round($price);
            $price = $this->_taxCalculator->round($price);
        }
        
        return $price;
    }
    
    /**
     *
     * @param Mage_Sales_Model_Quote_Item $item
     */
    protected function _resetItemDiscounts($item)
    {
        if (!$item) {
            return $this;
        }

        if ($item->getRowTotalBeforeRedemptions() == 0) {
            $item->setRowTotalBeforeRedemptions($item->getRowTotal());
            $item->setRowTotalBeforeRedemptionsInclTax($item->getRowTotalInclTax());
        } elseif ($item->getRowTotalBeforeRedemptions() < $item->getRowTotal()) {
            $item->setRowTotal($item->getRowTotalBeforeRedemptions());
            $item->setRowTotalInclTax($item->getRowTotalBeforeRedemptionsInclTax());
        } else {
            // do nothing
        }

        return $this;
    }
    
    /**
     * Calculates tax amounts for the row item using $this->_taxCalculator
     *
     * @param Mage_Sales_Model_Quote_Item $item
     *
     * @return $this
     */
    protected function _calcTaxAmounts(&$item)
    {
        //@nelkaake -a 16/11/10: Calculator only works in magento 1.4 and up.
        if (!Mage::helper('rewards/version')->isMageVersionAtLeast('1.4.2.0')) {
            return $this;
        }

        list($rowTax, $baseRowTax) = $this->_calcItemTax($item);

        // if this is a bundle product, our tax calculation needs to include its children
        if ($item->getHasChildren() && $item->isChildrenCalculated()) {
            $accumulatedTax     = 0.0;
            $accumulatedBaseTax = 0.0;
            foreach ($item->getChildren() as $child) {
                list($childTax, $childBaseTax) = $this->_calcItemTax($child);

                $accumulatedTax += $childTax;
                $accumulatedBaseTax += $childBaseTax;
            }

            $rowTax += $accumulatedTax;
            $baseRowTax += $accumulatedBaseTax;
        }

        $item->setTaxAmount(max(0, $rowTax));
        $item->setBaseTaxAmount(max(0, $baseRowTax));

        return $this;
    }

    /**
     * Calculates row item tax taking into account Tax settings: Apply Customer Tax, Apply Discount On Prices.
     *
     * @param  Mage_Sales_Model_Quote_Item $item
     *
     * @return array Item tax & base tax amounts
     */
    protected function _calcItemTax($item)
    {
        // Check if tax amounts for this item were already calculated
        $itemId = $item->getId();
        if (array_key_exists($itemId, $this->_calculatedTaxAmounts)) {
            return $this->_calculatedTaxAmounts[$itemId];
        }
        
        $store        = $item->getStoreId();
        $inclTax      = $this->_taxConfig->priceIncludesTax($store);
        $subtotal     = $inclTax ? $item->getRowTotalInclTax() : $item->getRowTotal();
        $baseSubtotal = $inclTax ? $item->getBaseRowTotalInclTax() : $item->getBaseRowTotal();
        $rate         = (string)$item->getTaxPercent();

        switch ($this->_taxHelper->getCalculationSequence($store)) {
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                // nothing to do here
                break;
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                $discountAmount     = $item->getDiscountAmount();
                $baseDiscountAmount = $item->getBaseDiscountAmount();
                $subtotal           = max($subtotal - $discountAmount, 0);
                $baseSubtotal       = max($baseSubtotal - $baseDiscountAmount, 0);
        }

        $itemTax     = $this->_taxCalculator->calcTaxAmount($subtotal, $rate, $inclTax, false);
        $baseItemTax = $this->_taxCalculator->calcTaxAmount($baseSubtotal, $rate, $inclTax, false);
        
        $itemTax = $this->_deltaRound($itemTax, $rate, $inclTax);
        $baseItemTax = $this->_deltaRound($baseItemTax, $rate, $inclTax, 'base');

        $item->setTaxAmount(max(0, $itemTax));
        $item->setBaseTaxAmount(max(0, $baseItemTax));

        // Save result so we don't calculate them again and so that the delta round is not messed up
        $taxArray = array($itemTax, $baseItemTax);
        $this->_calculatedTaxAmounts[$item->getId()] = $taxArray;
        return $taxArray;
    }
    
    /**
     * Adjusts a reedeemed points hash
     *
     * @throws Exception
     *
     * @param timestamp $date
     * @param int $wId
     * @param int $gId
     * @param int $pId
     * @param Mage_Sales_Model_Quote_Item $item
     * @param int $applyRuleId
     * @param int $qty
     * @param boolean $adjustQty	if true will set the price for that rule to the given qty, otherwise will add to the qty
     */
    private function _updateRedeemedPointsHash($date, $wId, $gId, $pId, $item, $applyRuleId, $qty, $adjustQty = true, $uses = 1, $overwrite = false) 
    {
        $modFlag = false;
        $customerCanAfford = true;
        $guestAllowed = true;
        
        $applicableRule = Mage::getResourceModel('rewards/catalogrule_rule')
            ->getApplicableReward($date, $wId, $gId, $pId, $applyRuleId);
        $rule = Mage::getModel('rewards/catalogrule_rule')->load($applyRuleId);
        $product = Mage::getModel('rewards/catalog_product')->load($pId);
        $currencyId = $applicableRule[self::POINTS_CURRENCY_ID];
        $pointsAmount = $applicableRule[self::POINTS_AMT];
        $toSpend = array($currencyId => $pointsAmount);

        if ($customer = $this->_aggregatedCart->getCustomer()) {
            $customerCanAfford = $customer->canAfford($toSpend);
        } else {
            $guestAllowed = Mage::helper('rewards/config')
                ->canUseRedemptionsIfNotLoggedIn();
        }

        $maxUses = $rule->getPointsUsesPerProduct();
        
        if (!empty($maxUses)) {
            if ($maxUses < $uses) {
                $uses = $maxUses;
            }
        }

        if (!$applicableRule) {
            throw new Exception("One or more of points redemptions you are trying to do are no longer available. Please refresh the page.");
        }

        $redeemedPoints = $applicableRule;
        $redeemedPoints[self::APPLICABLE_QTY] = $qty;
        $redeemedPoints[self::POINTS_USES] = $uses;

        $productPrice = Mage::helper('rewards/price')->getItemProductPrice($item);
        
        if (!$productPrice) {
            Mage::helper('rewards')->notice("Price was 0.00 but the user tried to redeem point on the item.  You cannot allow customers to redeem points on a 0.00 product.  If you're trying to allow customers to *buy* products with points instead of money, set the normal price and add a redemption rule that sets the product price to $0 with X points.");
            return $this;
        }

        $ccRatio = 0;
        
        if ($productPrice > 0) {
            $cc = $item->getQuote()->getStore()->getCurrentCurrency();
            $bc = 1 / ($item->getQuote()->getStore()->getBaseCurrency()->getRate($cc));
            $ccRatio = $bc;
        }
        
        $productPrice = $ccRatio * $productPrice;
        $redeemedPoints[self::POINTS_EFFECT] = Mage::helper('rewards')
            ->amplifyEffect($productPrice, $redeemedPoints[self::POINTS_EFFECT], $uses);

        // !!! TODO: REWRITE HELPER CATALOG POINTS CALCULATION
        $points = Mage::helper('rewards/transfer')
            ->calculateCatalogPoints($applyRuleId, $item, true);
        
        if (!$points) {
            throw new Exception(Mage::helper('rewards')->__('The catalog redemption rule entitled %s is invalid and cannot be applied.', $rule->getName()));
        }
        
        $redeemedPoints[self::POINTS_AMT] = $uses * $points['amount'] * - 1;

        $oldRedeemedPoints = Mage::helper('rewards')
            ->unhashIt($item->getRedeemedPointsHash());

        $newRedeemedPoints = $oldRedeemedPoints; // copy data from OLD to NEW

        $numProductsCurrentlyAffected = 0;
        
        foreach ($newRedeemedPoints as $i => &$oldRedeemedPointsLine ) {
            $oldRedeemedPointsLine = (array) $oldRedeemedPointsLine;
            $numProductsCurrentlyAffected += $oldRedeemedPointsLine[self::APPLICABLE_QTY];
        }
        
        $availExtraApplic = $item->getQty() - $numProductsCurrentlyAffected + (int)$qty;

        $numRedemptionInstances = 1;
        
        foreach ($newRedeemedPoints as $i => &$oldRedeemedPointsLine ) {
            $sameRuleId = $oldRedeemedPointsLine[self::POINTS_RULE_ID] == $applyRuleId;
            $sameEffects = $oldRedeemedPointsLine[self::POINTS_EFFECT] == $redeemedPoints[self::POINTS_EFFECT];
            $sameNumUses = $oldRedeemedPointsLine[self::POINTS_USES] == $uses;
            
            if ($sameRuleId) {
                if ($overwrite) {
                    if ($uses == 0) {
                        unset($newRedeemedPoints[$i]);
                    } else {
                        $redeemedPoints[self::POINTS_INST_ID] = $oldRedeemedPointsLine[self::POINTS_INST_ID];
                        $oldRedeemedPointsLine = $redeemedPoints;
                    }
                    
                    $modFlag = true;
                } else if ($sameEffects && $sameNumUses) {
                    // Double check that the customer can use the rule that many times
                    if ($adjustQty) {
                        // Just append the cost with the adjustment qty
                        $newApplicQty = ($redeemedPoints[self::APPLICABLE_QTY] + $oldRedeemedPointsLine[self::APPLICABLE_QTY]);
                        // Check if we have room to add this redemption rule
                        if ($redeemedPoints[self::APPLICABLE_QTY] > $availExtraApplic) {
                            /* throw new Exception("You cannot apply $qty redemptions (max is $avail_extra_applic) ".
                                " without overlapping with the other redemptions ".
                                " (product id is $pId rule was $apply_rule_id and website $wId. ");
                            */
                            return $this;
                        }
                    } else {
                        // Set the qty manually.
                        $newApplicQty = $redeemedPoints[self::APPLICABLE_QTY];
                        
                        if ($qty > 0) {
                            // set the qty
                            if ($newApplicQty > $availExtraApplic) {
                                /* throw new Exception("You cannot apply $qty redemptions (max is $avail_extra_applic) ".
                                    " without overlapping with the other redemptions ".
                                    " (product id is $pId rule was $apply_rule_id and website $wId. ");
                                */
                                return $this;
                            }
                        }
                    }
                    
                    $oldRedeemedPointsLine[self::APPLICABLE_QTY] = $newApplicQty;
                    
                    if (!isset($oldRedeemedPointsLine[self::POINTS_USES])) {
                        $old_redeemed_points_line [self::POINTS_USES] = 0;
                    }
                    
                    $modFlag = true;
                }
            }
            
            $numRedemptionInstances++;
        }
        
        if (!$modFlag && $qty != 0 && $uses != 0) {
            $redeemedPoints[self::POINTS_INST_ID] = $numRedemptionInstances;
            $newRedeemedPoints[] = $redeemedPoints;
            $modFlag = true;
        }

        $newRedeemedPointsHash = Mage::helper('rewards')->hashIt($newRedeemedPoints);
        $item->setRedeemedPointsHash($newRedeemedPointsHash);
        $item->unsetData("row_total_before_redemptions");

        if ($item->getId()) {
            $item->save ();
        }

        return $this;
    }

    /**
     * Ensure Product
     * @param Mage_Catalog_Model_Product|TBT_Rewards_Model_Catalog_Product $product
     * @return type
     */
    protected function _ensureProduct($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $product = TBT_Rewards_Model_Catalog_Product::wrap($product);
        }

        return $product;
    }
}