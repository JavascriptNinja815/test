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
 * Helper Transfer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Transfer extends Mage_Core_Helper_Abstract 
{
    /**
     * Creates a customer point-transfer of any amount or currency.
     *
     * @param  int $numPoints                : Quantity of points to transfer: positive=>distribution, negative=>redemption
     * @param  Mage_Sales_Model_Order $order  :  The order
     * @param  int $ruleId                   : The ID of the rule that allowed this transfer to be created... RULE MAY HAVE BEEN DISCONTINUED
     * @return boolean                        : whether or not the point-transfer succeeded
     */

    public function transferOrderPoints($numPoints, $order, $ruleId)
    {
        if (is_numeric($order)) {
            $order = Mage::getModel('sales/order')->load($order);
        }
        
        $orderId = $order->getId();
        $customerId = $order->getCustomerId();

        if (!$orderId || !$customerId) {
            return false;
        }

        $transfer = $this->initTransfer($numPoints, $ruleId, $customerId, (bool) $order->getCustomerId());
        if (!$transfer) {
            return false;
        }
        
        $transfer->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('order'));
        if (!$transfer->setStatusId(null, Mage::helper('rewards/config')->getInitialTransferStatusAfterOrder())) {
            return false;
        }
        
        if ($numPoints > 0) {
            $transfer->setComments(Mage::getStoreConfig('rewards/transferComments/orderEarned'));
        } else if ($numPoints < 0) {
            $transfer->setComments(Mage::getStoreConfig('rewards/transferComments/orderSpent'));
        }
        
        $transfer->setOrderId($orderId)->setCustomerId($customerId)->save();
        return true;
    }

    /**
     * Creates a customer point-transfer of any amount or currency.
     *
     * @param  int $num_points    : Quantity of points to transfer: positive=>distribution, negative=>redemption
     * @param  int $rule_id       : The ID of the rule that allowed this transfer to be created... RULE MAY HAVE BEEN DISCONTINUED
     * @return boolean            : whether or not the point-transfer succeeded
     */
    public function transferSendfriendPoints($num_points, $rule_id, $productId) {
        $transfer = $this->initTransfer ( $num_points, $rule_id );        
        if (! $transfer) {
            return false;
        }

        $transfer->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('send_friend'))
            ->setReferenceId($productId);

        // get the default starting status - usually Pending
        if (! $transfer->setStatusId ( null, Mage::helper ( 'rewards/config' )->getInitialTransferStatusAfterSendfriend () )) {
            // we tried to use an invalid status... is getInitialTransferStatusAfterReview() improper ??
            return false;
        }
        $transfer->setComments ( Mage::getStoreConfig ( 'rewards/transferComments/tellAFriendEarned' ) )->setCustomerId ( Mage::getSingleton ( 'customer/session' )->getCustomerId () )->save ();

        return true;
    }

    /**
     * Creates a customer point-transfer of any amount or currency.
     *
     * @param  int $num_points    : Quantity of points to transfer: positive=>distribution, negative=>redemption
     * @param  int $rule_id       : The ID of the rule that allowed this transfer to be created... RULE MAY HAVE BEEN DISCONTINUED
     * @return boolean            : whether or not the point-transfer succeeded
     * @deprecated from version 1.7.6.3+
     */
    public function transferPollPoints($num_points, $poll_id, $rule_id) {
        $transfer = $this->initTransfer ( $num_points, $rule_id );        
        if (! $transfer) {
            return false;
        }

        $transfer->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('poll'));
        
        // get the default starting status - usually Pending
        if (! $transfer->setStatusId ( null, Mage::helper ( 'rewards/config' )->getInitialTransferStatusAfterPoll () )) {
            // we tried to use an invalid status... is getInitialTransferStatusAfterReview() improper ??
            return false;
        }
        $transfer->setPollId ( $poll_id )->setComments ( Mage::getStoreConfig ( 'rewards/transferComments/pollEarned' ) )->setCustomerId ( Mage::getSingleton ( 'customer/session' )->getCustomerId () )->save ();

        return true;
    }
    
    /**
     * Creates a customer point-transfer of any amount or currency.
     *
     * @param  int $num_points    : Quantity of points to transfer: positive=>distribution, negative=>redemption
     * @param  int $customer_id
     * @param  int $rule          : The rule model that allowed this transfer to be created... RULE MAY HAVE BEEN DISCONTINUED
     * @return boolean            : whether or not the point-transfer succeeded
     */
    public function transferSignupPoints($num_points, $customer_id, $rule) {
        // ALWAYS ensure that we only give an integral amount of points
        $num_points = floor ( $num_points );

        if ($num_points == 0) {
            return false;
        }

        $transfer = Mage::getModel ( 'rewards/transfer' );
        $currency_id = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        if ((Mage::getModel('rewards/customer')->loadPointsBalance()->getUsablePointsBalance($currency_id) + $num_points) < 0) {
            throw Exception ('Your points balance cannot be negative.');
        }

        $reasonId = Mage::helper('rewards/transfer_reason')->getReasonId('signup');
        $transfer->setReasonId($reasonId);

        //get the default starting status - usually Pending
        if (! $transfer->setStatusId ( null, Mage::helper ( 'rewards/config' )->getInitialTransferStatusAfterSignup () )) {
            return false;
        }

        $transfer->setId(null)
            ->setQuantity($num_points)
            ->setComments(Mage::getStoreConfig('rewards/transferComments/signupEarned'))
            ->setRuleId($rule->getId())
            ->setCustomerId($customer_id)
            ->setReferenceId($customer_id)
            ->setAsSignup()
            ->save();

        return true;
    }
    
    /** 
     * Validate that the customer has enough points for this order
     * 
     * @param Mage_Sales_Model_Order $order
     * @throws Exception
     */
    public function validateCustomerBalance($order)
    {
        $customerId = $order->getCustomerId();
        
        if ($customerId) {
            $customer = Mage::getModel('rewards/customer')->load($customerId);
            $totalPointsSpent = array();

            // Collect spent cart points
            $cartTransfers = Mage::getSingleton('rewards/observer_sales_carttransfers');
            foreach ($cartTransfers->getRedemptionRuleIds() as $ruleId) {
                $cartPoints = Mage::getSingleton('rewards/session')->calculateCartPoints($ruleId, $order->getAllItems(), true);
                
                if (!is_array($cartPoints)) {
                    continue;
                }
                
                if (!array_key_exists($cartPoints['currency'], $totalPointsSpent)) {
                    $totalPointsSpent[$cartPoints['currency']] = 0;
                }
                
                $totalPointsSpent[$cartPoints['currency']] += $cartPoints['amount'];
            }

            $pointsObj = new Varien_Object(array('points' => array()));
            Mage::dispatchEvent('rewards_tally_points_spent_on_cart_additional', array('points_obj' => $pointsObj, 'order' => $order, 'items' => $order->getAllItems()));

            $totalPointsSpent = $pointsObj->getPoints();
            
            foreach ($totalPointsSpent as $currency => $amount) {
                if (($customer->getUsablePointsBalance($currency) + $amount) < 0) {
                    $error = $this->__( 'Not enough points for transaction. You have %s, but you need %s.', 
                        Mage::getModel('rewards/points')->set($currency, $customer->getUsablePointsBalance($currency)), 
                        Mage::getModel('rewards/points')->set($currency, $amount * -1)
                    );
                    throw new Exception($error);
                }
            }
        }
    }

    /**
     * Initiates a transfer model based on given criteria and verifies usage.
     *
     * @deprecated As of Sweet Tooth 1.5 and up functions should call their own
     * derivation of the TBT_Rewards_Model_Transfer model which contains this method.
     *
     * @param integer $num_points
     * @param integer $rule_id
     * @return TBT_Rewards_Model_Transfer
     */
    public function initTransfer($num_points, $rule_id, $customerId = null, $skipChecks = false) {
        if (
            !$skipChecks
            && !Mage::getSingleton('rewards/session')->isCustomerLoggedIn() 
            && !Mage::getSingleton('rewards/session')->isAdminMode() 
            && !Mage::getSingleton('rewards/session')->isRecurringOrderBeingPlaced()
        ) {
            return null;
        }
        // ALWAYS ensure that we only give an integral amount of points
        $num_points = floor ( $num_points );

        if ($num_points == 0) {
            return null;
        }

        $transfer = Mage::getModel ( 'rewards/transfer' );
        if ($num_points <= 0) {
            $customerId = $customerId ? $customerId : Mage::getSingleton('customer/session')->getCustomerId();
            $customer = Mage::getModel('rewards/customer')->load($customerId);

            $currency_id = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            if (($customer->getUsablePointsBalance ( $currency_id ) + $num_points) < 0) {
                $error = $this->__ ( 'Not enough points for transaction. You have %s, but you need %s.', Mage::getModel ( 'rewards/points' )->set ( $currency_id, $customer->getUsablePointsBalance ( $currency_id ) ), Mage::getModel ( 'rewards/points' )->set ( $currency_id, $num_points * - 1 ) );
                throw new Exception ( $error );
            }
        }

        $now = Mage::getModel('core/date')->gmtDate();
        $transfer->setId(null)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setQuantity($num_points)
            ->setCustomerId($customerId)
            ->setRuleId($rule_id);

        return $transfer;
    }

    /**
     * Gets a list of all rule ID's that are associated with the given order/shoppingcart/quote.
     * @deprecated.  Use order->getAPpliedDistriCartRuleIds() instead.
     *
     * @param   Mage_Sales_Model_Order  $order  : The order object with which the returned rules are associated
     * @return  array(int)                      : An array of rule ID's that are associated with the order
     */
    public function getCartRewardsRuleIds($order) {
        /* TODO: make this method return REWARDS-SYSTEM rule id's ONLY */
        /* TODO - from JAY: You can do this by using the rewards/catalog_rule or rewards_salesrule_rule models. */
        // look up all rule ID's associated with this order, or shopping cart
        $rule_ids_string = $order->getAppliedRuleIds ();
        if (empty ( $rule_ids_string )) {
            $rule_ids = array ();
        } else {
            $rule_ids = explode ( ',', $rule_ids_string );
            $rule_ids = array_unique ( $rule_ids );
        }
        return $rule_ids;
    }

    /**
     * Returns the rewards shopping cart rule points action singleton
     *
     * @return TBT_Rewards_Model_Catalogrule_Actions
     */
    private function getActionsSingleton() {
        return Mage::getSingleton ( 'rewards/salesrule_actions' );
    }

    /**
	 * Get request for product add to cart procedure
	 *
	 * @param   mixed $requestInfo
	 * @return  Varien_Object
	 */
	protected function _getProductRequest($requestInfo) {
		if ($requestInfo instanceof Varien_Object) {
			$request = $requestInfo;
		} elseif (is_numeric ( $requestInfo )) {
			$request = new Varien_Object ();
			$request->setQty ( $requestInfo );
		} else {
			$request = new Varien_Object ( $requestInfo );
		}

		if (! $request->hasQty ()) {
			$request->setQty ( 1 );
		}
		return $request;
	}

    private function hasGetProductFunc($obj) {
        $ret = false;
        if ($this->isItem ( $obj ) || $obj instanceof Varien_Object) { // params are function($rule)
            $ret = true;
        }
        return $ret;
    }

    private function isItem($obj) {
        $ret = false;
        if ($obj instanceof Mage_Sales_Model_Quote_Item || $obj instanceof Mage_Sales_Model_Quote_Item_Abstract || $obj instanceof Mage_Sales_Model_Quote_Address_Item || $obj instanceof Mage_Sales_Model_Order_Item || $obj instanceof Mage_Sales_Model_Order_Invoice_Item || $obj instanceof Mage_Sales_Model_Order_Creditmemo_Item || $obj instanceof Mage_Sales_Model_Order_Shipment_Item) { // params are function($rule)
            $ret = true;
        }
        return $ret;
    }

    /**
     * Calculates the amount of points to be given or deducted from a customer's cart, given the
     * rule that is being executed and possibly a list of items to act upon, if applicable.
     *
     * @deprecated ??? not sure if this is deprecated... see calculateCartPoints in Rewards/session singleton
     *
     * @param   int                                 $rule_id            : the ID of the rule to execute
     * @param   array(Mage_Sales_Model_Quote_Item)  $order_items        : the list of items to act upon
     * @param   boolean                             $allow_redemptions  : whether or not to calculate redemption rules
     * @return  array                                                   : 'amount' & 'currency' as keys
     */
    public function calculateCartDiscounts($rule_id, $order_items) {
        $rule = $this->getSalesRule ( $rule_id );
        $crActions = $this->getActionsSingleton ();

        if ($rule->getId ()) {
            if ($crActions->isDeductPointsAction ( $rule->getPointsAction () )) {
                // give a flat number of points if this rule's conditions are met
                $discount = $rule->getPointsDiscountAmount ();
            } else if ($crActions->isDeductByAmountSpentAction ( $rule->getPointsAction () )) {
                // deduct a set qty of points per every given amount spent if this rule's conditions are met
                // - this is a total price amongst ALL associated items, so add it up
                $price = $this->getTotalAssociatedItemPrice ( $order_items, $rule->getId () );
                $points_to_transfer = $rule->getPointsAmount () * floor ( round($price / $rule->getPointsAmountStep (), 5) );

                if ($rule->getPointsMaxQty () > 0) {
                    if ($points_to_transfer > $rule->getPointsMaxQty ()) {
                        $points_to_transfer = $rule->getPointsMaxQty ();
                    }
                }

                $discount = $rule->getPointsDiscountAmount () * ($points_to_transfer / $rule->getPointsAmount ());
            } else if ($rule->getPointsAction () == 'deduct_by_qty') {
                // deduct a set qty of points per every given qty of items if this rule's conditions are met
                // - this is a total quantity amongst ALL associated items, so add it up
                $qty = $this->getTotalAssociatedItemQty ( $order_items, $rule->getId () );
                $points_to_transfer = $rule->getPointsAmount () * ($qty / $rule->getPointsQtyStep ());

                if ($rule->getPointsMaxQty () > 0) {
                    if ($points_to_transfer > $rule->getPointsMaxQty ()) {
                        $points_to_transfer = $rule->getPointsMaxQty ();
                    }
                }

                $discount = $rule->getPointsDiscountAmount () * ($points_to_transfer / $rule->getPointsAmount ());
            } else {
                // whatever the Points Action is set to is invalid
                // - this means no transfer of points
                $discount = 0;
            }

            return $discount;
        }

        return 0;
    }

    /**
     * Accumulates the quantity of all items out of a list that are associated with a given rule.
     *
     * @param   array(Mage_Sales_Model_Quote_Item)  $order_items    : list of items to look in
     * @param   int                                 $required_id    : ID of the rule with which to filter
     * @return  int                                                 : the total quantity of all associated items
     */
    public function getTotalAssociatedItemQty($order_items, $required_id) {
        $qty = 0;

        foreach ( $order_items as $item ) {
            // look up item rule ids
            $item_rule_ids = explode ( ',', $item->getAppliedRuleIds () );
            $item_rule_ids = array_unique ( $item_rule_ids );

            // TODO Sweet Tooth - change this inner loop into an array_search
            foreach ( $item_rule_ids as $item_rule_id ) {
                // instantiate an item rule and dump its data
                $item_rule = $this->getSalesRule ( $item_rule_id );

                if ($item_rule->getId () == $required_id) {
                    // add this associated item's quantity to the running total
                    if ($item->getOrderId ()) {
                        $qty += $item->getQtyOrdered ();
                    } else if ($item->getQuoteId ()) {
                        $qty += $item->getQty ();
                    }
                    break;
                }
            }
        }

        return $qty;
    }

    /**
     * Accumulates the price of all items out of a list that are associated with a given rule.
     *
     * @nelkaake Wednesday May 5, 2010: This should be moved to some other helper, not Transfer helper.
     * @nelkaake Added on Friday June 11, 2010: Added use_salesrule parameter
     * @param   array(Mage_Sales_Model_Quote_Item)  $order_items    : list of items to look in. Could be array or an object that implements an itteratable interface
     * @param   int                                 $required_id    : ID of the rule with which to filter
     * @param   TBT_Rewards_Model_Salesrule_Rule    [$use_salesrule=null]   : salesrule if this is a salesrule check
     * @param  $prediction_mode                                 : if enabled will add prices even though they may not be applied to the items
     * @return  float                                               : the total price of all associated items
     */
    public function getTotalAssociatedItemPrice($order_items, $required_id, $use_salesrule = null, $prediction_mode = false) {
        $price = 0;

        // Get the store configuration
        $prices_include_tax = Mage::helper ( 'tax' )->priceIncludesTax ();

        foreach ( $order_items as $item ) {
            if ($this->_skipItemSumCalc($item)) {
                continue;
            }

            //@nelkaake Added on Friday June 11, 2010:
            if ($use_salesrule != null) {
                if (! Mage::getSingleton ( 'rewards/salesrule_validator' )->itemHasAppliedRid ( $item->getId (), $required_id )) {
                    continue;
                }
            }
            // look up item rule ids
            $item_rule_ids = explode ( ',', $item->getAppliedRuleIds () );
            $item_rule_ids = $prediction_mode ? array ($required_id ) : $item_rule_ids;
            $item_rule_ids = array_unique ( $item_rule_ids );

            foreach ( $item_rule_ids as $item_rule_id ) {
                // instantiate an item rule and dump its data
                $item_rule = $this->getSalesRule ( $item_rule_id );

                if ($item_rule->getId () == $required_id) {
                    $baseRowTotal = $prices_include_tax
                        ? $item->getBaseRowTotalInclTax()
                        : $item->getBaseRowTotal();

                    $price += $baseRowTotal;

                    break;
                }
            }
        }

        if ($price < 0.00001 && $price > - 0.00001) {
            $price = 0;
        }
        return $price;
    }

    /**
     * Accumulates the discount of all items out of a list that are associated with a given rule.
     *
     * @param   array(Mage_Sales_Model_Quote_Item)  $order_items    : list of items to look in. Could be array or an object that implements an itteratable interface
     * @param   int                                 $required_id    : ID of the rule with which to filter
     * @return  float                                               : the total discount of all associated items
     */
    public function getTotalAssociatedItemDiscount($order_items, $required_id) {
        $discount = 0;

        foreach ( $order_items as $item ) {
            if ($this->_skipItemSumCalc($item)) {
                continue;
            }

            // look up item rule ids
            $item_rule_ids = explode ( ',', $item->getAppliedRuleIds () );
            $item_rule_ids = array_unique ( $item_rule_ids );

            foreach ( $item_rule_ids as $item_rule_id ) {
                if ($item_rule_id == $required_id) {
                    $discountTaxCompensation = $this->_getDiscountTaxCompensation($item);

                    // add this associated item's discount to the total discount amount
                    $discount += $item->getBaseDiscountAmount() + $discountTaxCompensation;

                    break;
                }
            }
        }

        return $discount;
    }
    
    /**
     * Get Discount Tax Compensation based on admin tax configs
     * @param Mage_Sales_Model_Quote_Address_Item $item
     * @return float
     */
    protected function _getDiscountTaxCompensation($item)
    {
        $pricesIncludeTax = Mage::helper ( 'tax' )->priceIncludesTax ();
        $discountIncludeTax = Mage::helper ( 'tax' )->discountTax();

        $value = 0;
        
        if (!$pricesIncludeTax && $discountIncludeTax) {
            $discountExclTax = $item->getBaseDiscountAmount() / (1 + ($item->getTaxPercent() / 100));
            $discountTaxCompensation = round(abs($item->getBaseDiscountAmount()) - abs($discountExclTax),4);
            $value = (-1) * $discountTaxCompensation;
        }
        
        if ($pricesIncludeTax && !$discountIncludeTax) {
            $discountTaxCompensation = round(abs($item->getBaseDiscountAmount()) * $item->getTaxPercent() / 100,4);
            $value = $discountTaxCompensation;
        }
        
        return $value;
    }

    /**
     *
     * @param Mage_Sales_Model_Quote_Address_Item $item
     */
    protected function _skipItemSumCalc($item) {
        if($item->getParentItem () ) {
            if(($item->getParentItem()->getProductType() != 'bundle')) {
                return true;
            } elseif (Mage::getStoreConfig('rewards/general/apply_rules_to_parent')) {
                return true;
            }
        }
        return false;
    }



    /**
     * Accumulates the profit of all items out of a list that are associated with a given rule.
     *
     * @param   array(Mage_Sales_Model_Quote_Item)  $order_items    : list of items to look in
     * @param   int                                 $required_id    : ID of the rule with which to filter
     * @return  float                                               : the total profit of all associated items
     */
    public function getTotalAssociatedItemProfit($order_items, $required_id) {
        $profit = 0;

        foreach ( $order_items as $item ) {
            // look up item rule ids
            $item_rule_ids = explode ( ',', $item->getAppliedRuleIds () );
            $item_rule_ids = array_unique ( $item_rule_ids );

            foreach ( $item_rule_ids as $item_rule_id ) {
                // instantiate an item rule and dump its data
                $item_rule = $this->getSalesRule ( $item_rule_id );

                if ($item_rule->getId () == $required_id) {
                    // add this associated item's quantity-price to the running total
                    $profit += $item->getPrice () - $item->getCost ();
                    break;
                }
            }
        }

        return $profit;
    }

    /**
     * Fetches a cached shopping cart rule model
     *
     * @param integer $rule_id
     * @return TBT_Rewards_Model_Salesrule_Rule
     */
    public function &getSalesRule($rule_id) {
        return Mage::helper ( 'rewards/rule' )->getSalesRule ( $rule_id );
    }

}
