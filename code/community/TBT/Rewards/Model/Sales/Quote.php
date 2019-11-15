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
 * Sales quote model
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Sales_Quote extends Mage_Sales_Model_Quote {
    /**
     * Loads in a quote and returns a points quote
     * This is just for developers using eclipse (for code assist)
     *
     * @param Mage_Sales_Model_Quote $product
     * @return TBT_Rewards_Model_Sales_Quote
     */
    public static function wrap(Mage_Sales_Model_Quote &$quote) {
        return $quote;
    }

    /**
     * True if the quote object has any applied redemptions
     *
     * @param TBT_Rewards_Model_Quote $quote
     * @return boolean
     */
    public function _hasAppliedCartRedemptions($quote = null) {
        if ($quote == null) {
            $quote = &$this;
        }
        $redeem_rules = explode ( ',', $quote->getAppliedRedemptions () );
        if (empty ( $redeem_rules )) {
            return false;
        }
        foreach ( $redeem_rules as $rr ) {
            if (! empty ( $rr )) {
                //@nelkaake Thursday April 22, 2010 02:28:43 AM : check for variable usable rules
                $rr_model = Mage::helper ( 'rewards/rule' )->getSalesRule ( $rr );
                if ($rr_model->getPointsAction () == TBT_Rewards_Model_Salesrule_Actions::ACTION_DISCOUNT_BY_POINTS_SPENT) {
                    if ($quote->getPointsSpending() > 0) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *
     *
     * @return TBT_Rewards_Model_Observer_Sales_Carttransfers
     */
    protected function _getCartTransfersSingleton() {
        return Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );
    }

    /** KJ 5/3/2012: I broke this logic out of the main collectQuoteToOrderTransfers()
     *  method so that it could be called within that method but also called within
     *  Quotetoorder::prepareCatalogPointsTransfers().  This is for the case of
     *  CHECKOUT_METHOD_REGISTER because although we don't want to actually prepare
     *  the points transfers, we do want to validate the quote so that if it's
     *  not valid (ex: not enough points available), we will throw an error
     *  before the order is saved.
     */
    public function validateQuoteToOrderTransfers()
    {
        $order_items = $this->getAllItems ();
        $spent_points = array();
        $catalog_redemptions = array();

        $this->_tallyCartRedemptions($spent_points);

        $this->_tallyCartAdditionalRedemptions($spent_points);

        foreach ($spent_points as $currency_id => $points_amount) {
            if (!$this->_getRewardsSession()->getCustomer()->canAfford($points_amount, $currency_id)) {
                //reset quote back to active
                $this->setIsActive(true);

                //delete any order/point related messages
                Mage::getSingleton('core/session')->getMessages()->deleteMessageByIdentifier('TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create(pending points)');

                throw new Mage_Core_Exception ( Mage::helper ( 'rewards' )->__ ( 'You do not have enough points to spend on this order.  Please return to your cart and remove necessary point redemptions.' ) );
            }
        }
    }

    protected function _tallyCartRedemptions(&$spent_points)
    {
        $applied = Mage::getModel ( 'rewards/salesrule_list_valid_applied' )->initQuote ( $this );
        foreach ($applied->getList() as $rule_id) {
            $points = $this->_getRewardsSession()->calculateCartPoints($rule_id, $this->getAllItems (), true);
            if (!is_array($points)) {
                continue;
            }

            // add points spent on the cart to the running tally
            if (isset($spent_points[$points['currency']])) {
                $spent_points[$points['currency']] -= $points['amount']; // subtract since this value is negative
            } else {
                $spent_points[$points['currency']] = $points['amount'] * -1;
            }
        }
    }

    protected function _tallyCartAdditionalRedemptions(&$spentPoints)
    {
        $pointsObj = new Varien_Object(array('points' => $spentPoints));

        Mage::dispatchEvent('rewards_tally_points_spent_on_cart_additional', array('points_obj' => $pointsObj, 'quote' => $this, 'items' => $this->getAllItems()));

        $spentPoints = $pointsObj->getPoints();
    }

    public function collectQuoteToOrderTransfers($reserveIncrementId = true) {

        $this->validateQuoteToOrderTransfers();

        $order_items = $this->getAllItems ();

        $spent_points = array();

        $this->_tallyCartRedemptions($spent_points);

        $this->_tallyCartAdditionalRedemptions($spent_points);

        // cleaning the spent_points array by removing any instances of 0 points
        foreach ($spent_points as $key => $value) {
            if ($value == 0) {
                unset($spent_points[$key]);
            }
        }

        if (!empty($spent_points) && !$this->_getRewardsSession()->isCustomerLoggedIn() && !$this->_getRewardsSession()->isAdminMode()) {
            throw new Mage_Core_Exception ( $this->_getRH ()->__ ( 'You must be logged in to spend points.  Please return to your cart and remove the applied point redemptions.' ) );
        }

        /* Start checking ALL points spent against customer balance */
        foreach ($spent_points as $currency_id => $points_amount) {
            $checkoutMethod = $this->getCheckoutMethod(true);

            if ($checkoutMethod == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER) {
                Mage::getSingleton("rewards/session")->clear();
                //Customer is auto logged in so set the checkout method to logged in
                $this->setCheckoutMethod(Mage_Sales_Model_Quote::CHECKOUT_METHOD_LOGIN_IN);
            }
        }
        /* End checking points against balance */

        /* Start checking if any cart EARNINGS are NEGATIVE (safeguard against bugs) */
        foreach ($this->_getRewardsSession()->updateShoppingCartPoints() as $points) {
            if (!is_array($points)) {
                continue;
            }

            if (isset($points['amount']) && $points['amount'] < 0) {
                $customer_id = 0;
                if ($this->getCustomer()) {
                    $customer_id = $this->getCustomer()->getId();
                }

                Mage::helper('rewards/debug')->log("Order failed due to an EARNING rule attempting to DEDUCT points from the customer.  Please contact MageRewards Support immediately.   Quote ID: [{$this->getId()}], Customer ID: [{$customer_id}], Point Earnings: [{$points['amount']}]");

                // log out the customer if they're registering at checkout, to ensure they can't place order again
                $checkoutMethod = Mage::helper ( 'rewards' )->isBaseMageVersionAtLeast ( '1.4.0.0' ) ?
                    $this->getCheckoutMethod(true) :
                    $this->getCheckoutMethod();
                if ($checkoutMethod == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER) {
                    Mage::getSingleton("customer/session")->clear();
                    Mage::getSingleton("rewards/session")->clear();
                    //Customer is auto logged in so set the new customer id to empty
                    $this->getCustomer()->setId(null);
                }

                throw new Mage_Core_Exception ( Mage::helper ( 'rewards' )->__ ( 'Points earned could not be processed on this order.  This error has been logged; please contact the store owner immediately.' ) );
            }
        }
        /* End checking if any cart earnings are negative */

        /* Start applying all redemptions */
        $applied = Mage::getModel ( 'rewards/salesrule_list_valid_applied' )->initQuote ( $this );
        $cart_redemptions = $this->_getCartTransfersSingleton ();
        $cart_redemptions->setRedemptionRuleIds ( $applied->getList () );
        /* End applying all redemptions */

        /* Start preparing cart points strings (redemptions & distributions) */
        if ($this->_getRewardsSession ()->getCustomerId ()) {
            $points_earning = $this->_getRewardsSession ()->getTotalPointsEarningAsString ();
            $points_spending = $this->_getRewardsSession ()->getTotalPointsSpendingAsString ();
            $cart_redemptions->setEarnedPointsString ( $points_earning );
            $cart_redemptions->setRedeemedPointsString ( $points_spending );
        }
        /* End preparing cart points strings */

        if ($reserveIncrementId) {
            $this->reserveOrderId();
        }
    }
    
    /**
     * Get the correct increment id (it's different when an order is edited)
     * @return string
     */
    public function getRealIncrementId()
    {
        $request = Mage::app()->getRequest();
        $handle = $request->getRouteName() 
            . '_' . $request->getControllerName()
            . '_' . $request->getActionName();
        
        if ($handle === 'adminhtml_sales_order_edit_save') {
            $oldOrder = Mage::getSingleton('adminhtml/session_quote')->getOrder();
            $originalIncrementId = ($oldOrder->hasOriginalIncrementId()) ? $oldOrder->getOriginalIncrementId() : $oldOrder->getIncrementId();
            return $originalIncrementId . '-' . ($oldOrder->getEditIncrement() + 1);
        } else {
            return $this->getReservedOrderId();
        }
    }

    public function getTotalBaseTax($cart_rule_id = null) {
        $tax_total = 0;
        foreach ( $this->getAllItems () as $item ) {
            $item_applied = Mage::getModel ( 'rewards/salesrule_list_item_applied' )->initItem ( $item );

            if (! $item->getId ())
                continue;
            if ($item->getParentItem ())
                continue;
            if (! empty ( $cart_rule_id ) && ! $item_applied->hasRuleId ( $cart_rule_id ))
                continue;
            $tax_total += $item->getBaseTaxAmount ();
        }
        //        Mage::log("Discountable tax \$\$ is {$tax_total}.");
        return $tax_total;
    }
    public function getTotalBaseShipping($cart_rule_id = null, $skipAppliedValidation = false) {
        $total_shipping = 0;
        foreach ( $this->getAllItems () as $item ) {

            $item_applied = Mage::getModel ( 'rewards/salesrule_list_item_applied' )->initItem ( $item );

            if (! $item->getId ())
                continue;
            if ($item->getParentItem ())
                continue;
            if (! empty ( $cart_rule_id ) && ! $item_applied->hasRuleId ( $cart_rule_id ) && !$skipAppliedValidation)
                continue;
            $shipaddr = $item->getQuote ()->getShippingAddress ();
            $total_shipping = $shipaddr->getBaseShippingAmount (); //@nelkaake 17/03/2010 12:04:27 AM : This is like this on purpose
            if ($shipaddr->hasOriginalBaseShippingAmount ()) {
                $total_shipping = $shipaddr->getOriginalBaseShippingAmount (); //@nelkaake : If it exists, use this one instead since it's unaltered.
                $shippingTaxClass = Mage::helper ( 'tax' )->getShippingTaxClass ( $this->getStore () );
            }
        }
        //Mage::log("Discountable shipping \$\$ is {$total_shipping}.");
        return $total_shipping;
    }

    public function getTotalBaseAdditional($rule, $skipAppliedValidation = false)
    {
        $total_additional = 0;

        if ($rule->getApplyToShipping ()) {
            $total_additional += $this->getTotalBaseShipping ( $rule->getId () , $skipAppliedValidation);
        }

        if (
            Mage::helper('tax')->discountTax($this->getStore())
            && !Mage::helper('tax')->applyTaxAfterDiscount($this->getStore())
        ) {
            // We need to subtract the amount that was already discounted from the tax by catalog rules.
            if (!Mage::helper('tax')->priceIncludesTax($this->getStore())) {
                $total_additional += $this->getTotalBaseTax ();
                $total_additional -= $this->getRewardsDiscountTaxAmount ();
            }
        }
        //Mage::log("Discountable additional \$\$ is {$total_additional}.");
        return $total_additional;
    }

    public function getAssociatedBaseTotal($cart_rule_id, $skipAppliedValidation = false) {
        $price = 0;
        //Mage::log("Checking total against rule #{$cart_rule_id}");
        // Get the store configuration
        $prices_include_tax = Mage::helper ( 'tax' )->priceIncludesTax ( $this->getStore () );

        foreach ( $this->getAllAddresses () as $address ) {

            foreach ( $address->getAllItems () as $item ) {
                $item_applied = Mage::getModel ( 'rewards/salesrule_list_item_applied' )->initItem ( $item );

                if ($this->_skipItemSumCalc($item)) {
                    continue;
                }

                if (! $item_applied->hasRuleId ( $cart_rule_id ) && !$skipAppliedValidation) {
                    continue;
                }
                if ($prices_include_tax) {
                    $price += $item->getBaseRowTotalInclTax();
                } else {
                    $price += $item->getBaseRowTotal();
                }
            }
        }
        if ($price < 0.00001 && $price > - 0.00001) {
            $price = 0;
        }
        return $price;
    }

    /**
     *
     * @param Mage_Sales_Model_Quote_Address_Item $item
     */
    protected function _skipItemSumCalc($item) {
        if($item->getParentItem () ) {
            if(($item->getParentItem()->getProductType() != 'bundle')) {
                return true;
            }  elseif (Mage::getStoreConfig('rewards/general/apply_rules_to_parent')) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param array $rule_ids
     * @return TBT_Rewards_Model_Salesrule_Rule
     */
    protected function _getHighestPriorityRule($rule_ids) {
        $highest_priority_rule = null;
        foreach ( $rule_ids as $rid ) {
            $salesrule = Mage::helper ( 'rewards/transfer' )->getSalesRule ( $rid );
            if ($salesrule->getPointsAction () != 'discount_by_points_spent')
                continue; //@nelkaake Friday April 6, 2010 03:45:29 AM :
            if ($highest_priority_rule == null) {
                $highest_priority_rule = $salesrule;
                continue;
            }
            if ($salesrule->getSortOrder () < $highest_priority_rule->getSortOrder ()) {
                $highest_priority_rule = $salesrule;
                continue;
            }
        }
        return $highest_priority_rule;
    }


    /*
     * Return an address associated with this quote
     * returns null if none available
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _getAssociatedAddress() {
        $address = null;
        if ($this->isVirtual()) {
            $address = $this->getBillingAddress();
        } else {
            $address = $this->getShippingAddress();
        }
        return $address;
    }

    /**
     * @see _getAssociatedAddress()
     */
    public function getAssociatedAddress(){
        /*
         * //@mhadianfard -a 25/11/11:
         * not sure why the other function here is protected,
         * rewriting it so we can have public access without
         * breaking possibledependencies.
         */
        return $this->_getAssociatedAddress();
    }

    /**
     * Calculates the maximum points usable using spending rules
     * for this quote model.
     * Initiates points_step and min_spendable_points, max_spendable_points
     * local variables.
     * @return $this
     */
    protected function _calculateMaxPointsUsage() {
        if ($this->getHasCalculatedMaxUsage ())
            return $this;

        $quote = &$this;
        $store = $quote->getStore ();
        $applied = Mage::getModel('rewards/salesrule_list_valid_applied')->initQuote($this);

        $applicable = Mage::getModel('rewards/salesrule_list_valid_applicable')->initQuote($this);
        $rule_ids = array_merge($applied->getList(), $applicable->getList());

        // First select the highest priority rule that applies to the quote
        $highest_priority_rule = $this->_getHighestPriorityRule ( $rule_ids );
        $salesrule = $highest_priority_rule;

        if ($highest_priority_rule != null) {
            $spendings_discount = $this->getTotalBaseRewardsSpendingDiscount ();
            $quote_total = $this->getAssociatedBaseTotal ( $highest_priority_rule->getId () , true) + $this->getTotalBaseAdditional ( $salesrule , true);

            $quote_total_before_discounts = $quote_total;
            //echo("Discountable total is $quote_total_before_discounts + {$this->getShippingAddress()->getDiscountAmount()} + $spendings_discount = {$quote_total}.");

            //@nelkaake Added on Wednesday May 5, 2010:  Subtract any nonspending discounts
            $quote_total += $this->_getAssociatedAddress()->getDiscountAmount ();

            if (($salesrule->getPointsDiscountAction () == 'by_percent' && $quote_total > 0) || $salesrule->getPointsDiscountAction () != 'by_percent') {
                $quote_total += $spendings_discount;
            }

            $quote_total = max ( 0, $quote_total );
            //Mage::log("Discountable total \$\$ is {$this->getAssociatedBaseTotal()}.");
            $min_divisible_step = 1;
            $min = 0;
            $max = $quote->getBaseSubtotal () * 1000;
            $highest_priority_step = 0;
            $cust = Mage::getSingleton ( 'rewards/session' )->getSessionCustomer ();

            $salesrule = $highest_priority_rule;
            if ($highest_priority_step == 0 || $salesrule->getPriority () > $highest_priority_step) {
                $min_divisible_step = $salesrule->getPointsAmount ();
            }

            //@mhadianfard -c 16/11/10:
            if ($salesrule->getPointsDiscountAction () == 'by_percent') {
                $num_percents = 100; //ceil( ($quote_total_before_discounts) * 100 );


                //@nelkaake -a 16/11/10: Add 1 percent to accoutn for rounding error.
                if ($salesrule->getApplyToShipping ()) {
                    $num_percents += 1;
                }

                $num_percents = min ( $num_percents, 100 );

                $max = min ( $max, ceil ( (($num_percents) / $salesrule->getPointsDiscountAmount ()) ) * $min_divisible_step );
            } else {
                $max = min ( $max, ceil ( $quote_total / $salesrule->getPointsDiscountAmount () ) * $min_divisible_step );
                if (Mage::getSingleton ( 'rewards/session' )->isCustomerLoggedIn ()) {
                    $cust_usable_points = $cust->getUsablePointsBalance ( $salesrule->getPointsCurrencyId () );
                    $cust_usable_points_even = $cust_usable_points - ($cust_usable_points % $min_divisible_step);
                    $max = min ( $max, $cust_usable_points_even );
                }
            }

            if (sizeof ( $rule_ids ) <= 0) {
                $max = $min_divisible_step = $min = 0;
            }

            //@nelkaake Added on Sunday May 30, 2010:
            $max_points_spent = $salesrule->getPointsMaxQty ();
            if ($max_points_spent) {
                $max = min($max, $max_points_spent);
            }
            
            $numberOfSliderRules = Mage::getSingleton('rewards/session')->getSliderRulesCount();
            if ($numberOfSliderRules > 1) {
                $max = (int)($max / $numberOfSliderRules);
            }
            
            $customer_balance = $this->_getRewardsSession()->getCustomer()->getUsablePointsBalance($salesrule->getPointsCurrencyId());
            $max = min($max, $customer_balance);

            // truncate the overflow on the max usages to be a divisible step size
            if( $min_divisible_step > 1 && $max > 1 ) {
                $max = ((int)($max / $min_divisible_step)) * $min_divisible_step;
            }
        } else {
            $max = $min_divisible_step = $min = 0;
        }

        $this->setPointsStep ( $min_divisible_step );
        $this->setMinSpendablePoints ( $min );
        $this->setMaxSpendablePoints ( $max );
        $this->setHasCalculatedMaxUsage ( true );

        $this->_updatePointsSpent($min_divisible_step);
        /*echo ("Step: {$this->getPointsStep()}, Min: {$this->getMinSpendablePoints()}, ".
            "Max: {$this->getMaxSpendablePoints()} ");*/
        return $this;
    }

    /**
     * In limited scenarios where we had, for example, a slider rule applied to the cart, but after removing a product
     * that rule no longer applies but instead another slider rule applies, we are making sure here that the amount of
     * points spent on first rule is divisable with new rule points step. (check ST-1195 for more details)
     *
     * @param  int $pointsStep Rule points step
     * @return $this
     */
    protected function _updatePointsSpent($pointsStep)
    {
        if (!$pointsStep) {
            return $this;
        }

        $pointsSpending = $this->getPointsSpending();

        if (!$pointsSpending) {
            return $this;
        }

        $multiplier = floor($pointsSpending / $pointsStep);
        $pointsSpending = $multiplier * $pointsStep;
        $this->setPointsSpending($pointsSpending);

        return $this;
    }

    public function getPointsStep() {
        $this->_calculateMaxPointsUsage ();
        return $this->getData ( 'points_step' );
    }
    public function getMinSpendablePoints() {
        $this->_calculateMaxPointsUsage ();
        return $this->getData ( 'min_spendable_points' );
    }
    public function getMaxSpendablePoints() {
        $this->_calculateMaxPointsUsage();
        $maxUsablePoints = $this->getData('max_spendable_points');
        
        return max($maxUsablePoints, 0);
    }

    public function getTotalBaseRewardsSpendingDiscount() {
        $total = 0;
        foreach ( $this->getAllAddresses () as $address ) {
            $total += $address->getTotalBaseRewardsSpendingDiscount ();
        }
        return $total;
    }

    public function getPointsSpending()
    {
        return (int) $this->getData('rewards_points_spending');
    }

    public function setPointsSpending($pointsQuantity)
    {
        $this->setData('rewards_points_spending', (int) $pointsQuantity);

        return $this;
    }

    /**
     * Map Rewards Shopping Cart Spending Rules discount amounts and labels
     * @param TBT_Rewards_Model_Salesrule_Rule $rule
     * @param float $baseDiscountAmount
     * @param float $discountAmount
     * @return \TBT_Rewards_Model_Sales_Quote
     */
    public function appendRewardsCartDiscountAmounts($item, $rule, $baseDiscountAmount, $discountAmount)
    {
        /**
         * Append Rewards Cart Discount Amounts to Quote
         */
        $rewardsDiscountMap = $this->getRewardsCartDiscountMap();

        if (is_null($rewardsDiscountMap)) {
            $rewardsDiscountMap = array();
        } else {
            $rewardsDiscountMap = json_decode($this->getRewardsCartDiscountMap(), true);
        }

        if (!is_array($rewardsDiscountMap)) {
            return $this;
        }

        if (!isset($rewardsDiscountMap[$rule->getId()])) {
            $rewardsDiscountMap[$rule->getId()] = array(
                'label' => ($rule->getStoreLabel()) ?: '',
                'base_discount_amount' => $baseDiscountAmount,
                'discount_amount' => $discountAmount,
                'rule_id' => $rule->getId(),
                'rule_type' => 'rewards_shopping_cart_rule'
            );
        } else {
            $rewardsDiscountMap[$rule->getId()]['base_discount_amount'] += $baseDiscountAmount;
            $rewardsDiscountMap[$rule->getId()]['discount_amount'] += $discountAmount;
        }

        $jsonRewardsCartDiscountMap = json_encode($rewardsDiscountMap);
        $this->setRewardsCartDiscountMap($jsonRewardsCartDiscountMap);

        /**
         * Append Rewards Cart Discount Amounts to Quote Item
         */
        $itemRewardsDiscountMap = $item->getRewardsCartDiscountMap();

        if (is_null($itemRewardsDiscountMap)) {
            $itemRewardsDiscountMap = array();
        } else {
            $itemRewardsDiscountMap = json_decode($item->getRewardsCartDiscountMap(), true);
        }

        if (!is_array($itemRewardsDiscountMap)) {
            return $this;
        }

        if (!isset($itemRewardsDiscountMap[$rule->getId()])) {
            $itemRewardsDiscountMap[$rule->getId()] = array(
                'label' => ($rule->getStoreLabel()) ?: '',
                'base_discount_amount' => $baseDiscountAmount,
                'discount_amount' => $discountAmount,
                'rule_id' => $rule->getId(),
                'rule_type' => 'rewards_shopping_cart_rule'
            );
        } else {
            $itemRewardsDiscountMap[$rule->getId()]['base_discount_amount'] += $baseDiscountAmount;
            $itemRewardsDiscountMap[$rule->getId()]['discount_amount'] += $discountAmount;
        }

        $jsonItemRewardsCartDiscountMap = json_encode($itemRewardsDiscountMap);
        $item->setRewardsCartDiscountMap($jsonItemRewardsCartDiscountMap);

        return $this;
    }

    /**
     * Map Rewards Shopping Cart Spending Rules discount amounts and labels as array
     * @return array
     */
    public function getRewardsCartDiscountMapItems()
    {
        if (is_null($this->getRewardsCartDiscountMap())) {
            return array();
        }

        $mapItems = json_decode($this->getRewardsCartDiscountMap(), true);

        return $mapItems;
    }

    /**
     * Map Item Rewards Shopping Cart Spending Rules discount amounts and labels as array
     * @param mixed $item
     * @return array
     */
    public function getItemRewardsCartDiscountMapItems($item)
    {
        if (!$item) {
            return array();
        }
        
        if (is_null($item->getRewardsCartDiscountMap())) {
            return array();
        }

        $mapItems = json_decode($item->getRewardsCartDiscountMap(), true);

        return $mapItems;
    }

    protected function _getRedemptionSession()
    {
        $isAdminMode = Mage::app()->getStore()->isAdmin();
        if ($isAdminMode) {
            return Mage::getSingleton('adminhtml/session_quote');
        }

        return Mage::getSingleton('rewards/session');
    }

    /*
    protected function GCD($a, $b) {
        while ( $b != 0) {
            $remainder = $a % $b;
            $a = $b;
            $b = $remainder;
        }
        return abs ($a);
    }
    */

    /**
     * Fetches the rewards session
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession() {
        return Mage::getSingleton ( 'rewards/session' );
    }

    /**
     * Fetches the rewards transfer helper
     *
     * @return TBT_Rewards_Helper_Transfer
     */
    protected function _getTransferHelp() {
        return Mage::helper ( 'rewards/transfer' );
    }

    /**
     * Fetches the rewards config helper
     *
     * @return TBT_Rewards_Helper_Config
     */
    protected function _getCfg() {
        return Mage::helper ( 'rewards/config' );
    }

    /**
     * Fetches the rewards generic helper
     *
     * @return TBT_Rewards_Helper_Data
     */
    protected function _getRH() {
        return Mage::helper ( 'rewards' );
    }

}
