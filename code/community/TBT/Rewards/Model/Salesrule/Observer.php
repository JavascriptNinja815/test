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
 * Sales Rules Observer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Salesrule_Observer extends Varien_Object
{

    /*
     *  Stores the discount applied for a given rule id
     */
    protected $_hadDisc = array();
    
    /**
     * All processed rules 
     * @var array
     */
    protected $proccessedRules = array();

    /**
     * Remove rules from valid rules list when deleting an item
     * 
     * @param Varien_Object $e
     * @event sales_quote_remove_item
     */
    public function removeRulesForDeletedItem($e)
    {
        $item = $e->getEvent()->getQuoteItem();
        $appliedRuleIds = explode(',', $item->getAppliedRuleIds());
        
        if (!empty($appliedRuleIds)) {

            $quote = $item->getQuote();
            $valid = Mage::getModel('rewards/salesrule_list_valid')->initQuote($quote);

            foreach ($appliedRuleIds as $ruleId) {
                $valid->remove($ruleId);
            }
            
            $valid->saveToQuote($quote);
        }
    }
    
    /**
     * This gets triggered after rules are processed and the discount amount is requested.
     *
     * @param Varien_Object $o
     */
    public function salesruleValidatorProcess($o)
    {
        $event   = $o->getEvent();
        $quote   = $event->getQuote();
        $address = $event->getAddress();
        $item    = $event->getItem();

        $rule    = $event->getRule();
        $rule_id = $rule->getId();
        $this->proccessedRules[] = $rule_id;

        $is_applicable = false;
        
        try {
            //@nelkaake -a 11/03/11: FIRSTLY, if it's not a points rule, skip it and let Magento do it's thing.
            if (!Mage::helper('rewards/rule')->isPointsRule($rule)) {
                return $this;
            }

            //@nelkaake -a 11/03/11: Check to see if the rule is applied. If it is, allow it to be alive.
            $this->_getDiscountValidator()->validateRedemptionRule($quote, $address, $item, $rule_id, $is_applicable);

            //@nelkaake -a 11/03/11: Calculate any requird discounts
            $new_discounts = $this->_getDiscountCalculator()->getNewDiscounts($quote, $address, $item, $rule_id);

        } catch (Exception $e) {
            Mage::helper('rewards')->log(
                "An error occured while trying to process shopping cart points rules: " . $e->getMessage()
                . ". \nDiscounts have been reset as a result."
            );
            Mage::logException($e);

            //@nelkaake -a 11/03/11: Reset the discounts as a failsafe.
            $new_discounts = new Varien_Object (array('discount_amount' => 0, 'base_discount_amount' => 0));
        }

        // Don't apply any discounts to shipping until we assure this feature.
        // TODO allow shipping discounts
        $event->getRule()->setApplyToShipping(false);
        $event->getRule()->setSimpleFreeShipping(false);

        // we currrently don't support multi-shipping, so if it's a multi-shipping checkout don't apply cart discounts
        if (!Mage::helper('rewards')->isMultishipingCheckout($quote)) {
            $original_discounts = $event->getResult();
            $original_discounts->setDiscountAmount($new_discounts->getDiscountAmount());
            $original_discounts->setBaseDiscountAmount($new_discounts->getBaseDiscountAmount());

        }
        $this->_hadDisc[$rule_id] = array(
            "discount_amount"      => $new_discounts->getDiscountAmount(),
            "base_discount_amount" => $new_discounts->getBaseDiscountAmount()
        );

        if ($is_applicable) {
            $rule->setStopRulesProcessing(false);
        }

        return $this;
    }

    /**
     * @return TBT_Rewards_Model_Salesrule_Discount_Calculator
     */
    protected function _getDiscountCalculator()
    {
        return Mage::getSingleton('rewards/salesrule_discount_calculator');
    }

    /**
     * @return TBT_Rewards_Model_Salesrule_Discount_Validator
     */
    protected function _getDiscountValidator()
    {
        return Mage::getSingleton('rewards/salesrule_discount_validator');
    }

    /**
     *
     * Enter description here ...
     *
     * @param unknown_type $o
     */
    public function checkRedemptionCouponAfter($o)
    {
        $this->setRequest($o->getControllerAction()->getRequest());
        $this->setResponse($o->getControllerAction()->getResponse());
        $couponCode = ( string )$this->getRequest()->getParam('coupon_code');

        //@nelkaake Changed on Saturday August 17, 2010:
        $current_customer_group_id = Mage::getSingleton('rewards/session')->getCustomer()->getGroupId();
        $current_website_id        = Mage::app()->getStore()->getWebsiteId();

        $coupon = Mage::getModel('salesrule/coupon');
        $coupon->load($couponCode, 'code');
        $rr = Mage::getModel('rewards/salesrule_rule')->load($coupon->getRuleId());

        $is_redem = Mage::getSingleton('rewards/salesrule_actions')->isRedemptionAction($rr->getPointsAction());
        if ($is_redem) {
            ($couponCode == $this->_getQuote()->getCouponCode())
                ? $this->_redirect('rewards/cart_redeem/cartadd', array("rids" => $rr->getId(), "redirect-to-cart" => true))
                : $this->_redirect('rewards/cart_redeem/cartremove', array("rids" => $rr->getId(), "redirect-to-cart" => true));
        }

        return $this;
    }

    /**
     *
     * Enter description here ...
     *
     * @param unknown_type $o
     */
    public function checkRedemptionCouponBefore($o)
    {
        $this->setRequest($o->getControllerAction()->getRequest());
        $this->setResponse($o->getControllerAction()->getResponse());
        $couponCode = ( string )$this->getRequest()->getParam('coupon_code');

        //@nelkaake Changed on Saturday July 17, 2010:
        $current_customer_group_id = Mage::getSingleton('rewards/session')->getCustomer()->getGroupId();
        $current_website_id = Mage::app()->getStore()->getWebsiteId();

        $rrs = Mage::getModel('rewards/salesrule_rule')->getCollection();
        $rrs->setValidationFilter($current_website_id, $current_customer_group_id, $couponCode);
        
        $rr = $rrs->getFirstItem();
        $is_redem = Mage::getSingleton('rewards/salesrule_actions')->isRedemptionAction($rr->getPointsAction());
        if ($is_redem) {
            if ($couponCode != $this->_getQuote()->getCouponCode()) { //applying redemption coupon
                $this->_redirect('rewards/cart_redeem/cartremove', array("rids" => $rr->getId(), "redirect-to-cart" => true));
            }
        }

        return $this;
    }

    /**
     * This gets triggered before totals are collected so we can reset cart rule discounts
     *
     * @param Varien_Object $o
     */
    public function resetCartDiscounts($o)
    {
        $quote = $o->getEvent()->getQuote();

        $quote->setRewardsCartDiscountMap(null);

        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteItem->setRewardsCartDiscountMap(null);
        }

        //@nelkaake -a 11/03/11: Reset the counting discount field
        foreach ($quote->getAllAddresses() as $address) {
            $address->setRewardsCartRules(array());
            
            foreach ($address->getAllItems() as $item) {
                $item->setDiscountAmount(0);
                $item->setBaseDiscountAmount(0);
            }
        }

        return $this;
    }

    /**
     * This gets triggered AFTER totals are collected so we can make sure nothing went below 0
     * Sometimes rounding brings us bellow 0.0000000000001 or we get "-0.00".
     *
     * @param Varien_Object $o
     */
    public function cleanup($o)
    {
        $quote = $o->getEvent()->getQuote();
        $validAppliedModel = Mage::getModel('rewards/salesrule_list_applied')->initQuote($quote);
        $validApplicableModel = Mage::getModel('rewards/salesrule_list_valid_applicable')->initQuote($quote);
        
        $processedRules = array_unique($this->proccessedRules);
        $validAppliedRules = $validAppliedModel->getList();
        $validApplicableRules = $validApplicableModel->getList();
        
        foreach (array_diff($validAppliedRules, $processedRules) as $inactiveRule) {
            $validAppliedModel->remove($inactiveRule);
        }
        
        foreach (array_diff($validApplicableRules, $processedRules) as $inactiveRule) {
            $validApplicableModel->remove($inactiveRule);
        }
         
        $validAppliedModel->saveToQuote($quote);
        $validApplicableModel->saveToQuote($quote);
        $validAppliedList = $validAppliedModel->getList();
		
        if (empty($validAppliedList)) {
            $quote->setPointsSpending(0);
        }

        //@nelkaake -a 11/03/11: Reset the counting discount field
        foreach ($quote->getAllAddresses() as $address) {
            if ($address->getGrandTotal() <= -0) {
                $address->setGrandTotal(0);
            }
        }

        $this->cleanAppliedRuleIds($quote, $validAppliedList);

        return $this;
    }

    public function cleanAppliedRuleIds(&$quote, $validAppliedList)
    {
        if (!$quote->getAppliedRuleIds()) {
            return $this;
        }

        $ruleHelper = Mage::helper('rewards/rule');
        
        $quoteAppliedRuleIds = explode(',',$quote->getAppliedRuleIds());
        $ruleIdsToBeRemoved = array();

        foreach ($quoteAppliedRuleIds as $quoteAppliedRuleId) {
            $rule = Mage::helper ( 'rewards/rule' )->getSalesrule ( $quoteAppliedRuleId );

            if (!$rule->isPointsRule()) {
                continue;
            }

            if (!$rule->isRedemptionRule ()) {
                continue;
            }

            if (!in_array($quoteAppliedRuleId, $validAppliedList)) {
                $ruleIdsToBeRemoved[] = $quoteAppliedRuleId;
            }
        }

        $quote->setAppliedRuleIds(
            $ruleHelper->removeValuesFromStringArray($quote->getAppliedRuleIds(), $ruleIdsToBeRemoved)
        );

        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteItem->setAppliedRuleIds(
                $ruleHelper->removeValuesFromStringArray($quoteItem->getAppliedRuleIds(), $ruleIdsToBeRemoved)
            );
        }

        return $this;
    }

    /**
     * This resets shopping cart discount labels. Gets triggered after totals are collected,
     * on event 'sales_quote_collect_totals_before'
     *
     * @param Varien_Object $o
     */
    public function resetDiscountLabels($o)
    {
        $event = $o->getEvent();
        if (!$event) {
            return $this;
        }

        $quote = $event->getQuote();
        if (!$quote) {
            return $this;
        }

        $addresses = $quote->getAllAddresses();
        if (!is_array($addresses)) {
            return $this;
        }

        foreach ($addresses as $address) {
            $description = $address->getDiscountDescriptionArray();
            // if description is empty no need to continue this
            if (empty($description)) {
                continue;
            }

            $valid_applicable = Mage::getModel('rewards/salesrule_list_valid_applicable')->initQuote($quote);
            $applied          = Mage::getModel('rewards/salesrule_list_applied')->initQuote($quote);

            $description = $this->_unsetAppliedRuleDescription($applied, $description, $this->_hadDisc);
            $description = $this->_unsetValidRuleDescription($valid_applicable, $description);
            $description = $this->_cleanDiscountDescriptionArray($description);
            //set updated discount description
            $address->setDiscountDescription($description);
        }
        
        return $this;
    }

    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * Set redirect into responce
     *
     * @param   string $path
     * @param   array  $arguments
     */
    protected function _redirect($path, $arguments = array())
    {
        $this->getResponse()->setRedirect(Mage::getUrl($path, $arguments));

        return $this;
    }

    /*
     * reset labels for the applied rules when the discount is zero.
     *
     * @return array();
     */
    protected function _unsetAppliedRuleDescription($applied, $description, $hadDisc)
    {
        foreach ($applied->getList() as $rule_id) {
            if ($rule_id == null) {
                continue;
            }

            if (!isset($hadDisc[$rule_id])) {
                continue;
            }

            if ($hadDisc[(int)$rule_id]["discount_amount"] == 0) {
                if (!isset($description[$rule_id])) {
                    continue;
                }
                unset($description[$rule_id]);
            }
        }

        return $description;
    }

    /*
     *    going through the list of applicable rules ( that are valid, but not applied by user)
     *    and removing the labels for them
     * @return array()
     */
    protected function _unsetValidRuleDescription($valid_applicable, $description)
    {
        foreach ($valid_applicable->getList() as $rule_id) {
            // if description is not set for this rule continue
            if (!isset($description[$rule_id])) {
                continue;
            }
            unset($description[$rule_id]);
        }

        return $description;
    }

    /*
     *  Remove earning point rule id's if its set in the description array.
     *  @return array
     */
    protected function _cleanDiscountDescriptionArray($description)
    {
        foreach ($description as $key => $value) {
            $rule = Mage::getModel('rewards/salesrule_rule')->load($key);

            if (!isset($description[$key])) {
                continue;
            }

            if ($rule->isDistributionRule()) {
                unset($description[$key]);
            }
        }

        if (is_array($description) && !empty($description)) {
            $description = array_unique($description);
            $description = implode(', ', $description);
        } else {
            $description = '';
        }

        return $description;
    }
    
    /**
     * Magento does not track coupons with empty discount (which is the case
     * for earning rules). This method update coupon codes for 0 discount 
     * rules as well.
     *
     * @param Varien_Event_Observer $observer
     * @event sales_order_place_after
     * @see Mage_SalesRule_Model_Observer::sales_order_afterPlace()
     */
    public function couponCodeFix($observer)
    {
        if (!Mage::helper('rewards/version')->isBaseMageVersionAtLeast('1.8.0.0')) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return $this;
        }

        if (!$order->getAppliedRuleIds()) {
            return $this;
        }

        // lookup rule ids
        $ruleIds = explode(',', $order->getAppliedRuleIds());
        $ruleIds = array_unique($ruleIds);

        $ruleCustomer = null;
        $customerId = $order->getCustomerId();

        // use each rule (and apply to customer, if applicable)
        if ($order->getDiscountAmount() == 0 && count($ruleIds) > 0) {
            $salesRuleResource = Mage::getResourceModel('rewards/wrap_salesrule');
            $salesRuleResource->updateSalesrulesTimesUsed($ruleIds, $customerId);

            $coupon = Mage::getModel('salesrule/coupon');
            /** @var Mage_SalesRule_Model_Coupon */
            $coupon->load($order->getCouponCode(), 'code');
            if ($coupon->getId()) {
                $coupon->setTimesUsed($coupon->getTimesUsed() + 1);
                $coupon->save();
                if ($customerId) {
                    $couponUsage = Mage::getResourceModel('salesrule/coupon_usage');
                    $couponUsage->updateCustomerCouponTimesUsed($customerId, $coupon->getId());
                }
            }
        }
    }
}

