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
 * Cart Redeem Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Cart_RedeemController extends Mage_Core_Controller_Front_Action
{

    public function cartAction()
    {
        if (!($quote = $this->_loadValidQuote())) {
            return;
        }

        if (!($cartRedemptions = $this->_loadValidRedemptions())) {
            return;
        }

        $quote->setCartRedemptions($cartRedemptions)->save();
    }

    public function addAction()
    {
        $pollId   = intval($this->getRequest()->getParam('poll_id'));
        $answerId = intval($this->getRequest()->getParam('vote'));

        $poll = Mage::getModel('poll/poll')->load($pollId);

        // Check poll data
        if ($poll->getId() && !$poll->getClosed() && !$poll->isVoted()) {
            $vote = Mage::getModel('poll/poll_vote')->setPollAnswerId($answerId)
                ->setIpAddress(ip2long($this->getRequest()->getServer('REMOTE_ADDR')))
                ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());

            $poll->addVote($vote);
            Mage::getSingleton('core/session')->setJustVotedPoll($pollId);
        }

        $this->_redirectReferer();
    }

    protected function _getSess()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * AJAX action called from the Shopping Cart Points slider.
     * This will either output the shopping cart totals block or an error message string
     */
    public function changePointsSpendingAction()
    {
        $fromZeroGrandTotal = ($this->_getQuote()->getGrandTotal() < 0.00001) ? true : false;
        
        $newPointsSpending = $this->getRequest()->getParam("points_spending");
        if ($this->isValidSpendingAmount($newPointsSpending)) {
            $this->_getQuote()->setPointsSpending($newPointsSpending);
        }

        $this->_getCart()->init()->save();        
        $blocks = $this->fetchAjaxCartBlocks();
        
        $isZeroGrandTotal = ($this->_getQuote()->getGrandTotal() < 0.00001) ? true : false;
        
        if ($blocks) {
            $blocks['from_zero_grand_total'] = $fromZeroGrandTotal;
            $blocks['is_zero_grand_total'] = $isZeroGrandTotal;
            
            $this->getResponse()->setHeader('Content-Type', 'application/json', true);
            $this->getResponse()->setBody(Zend_Json::encode($blocks));
        }
    }
    
    /**
     * Adds a series of rule ids to the cart after validating them against the customers point balance
     * @param string $ruleIds
     */
    public function cartaddAction()
    {
        $result = $this->addCartCheckboxRule();
        $request = $this->getRequest();
        
        if (!$result['error']) {
            $blocks = $this->fetchAjaxCartBlocks();
            $result = array_merge($result, $blocks);
        }
        
        if ($request->get('redirect-to-cart') && !$request->isAjax()) {
            $this->_redirect('checkout/cart/');
            return;
        }
        
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function cartremoveAction()
    {
        $result = $this->removeCartCheckboxRule();
        $request = $this->getRequest();
        
        if (!$result['error']) {
            $blocks = $this->fetchAjaxCartBlocks();
            $result = array_merge($result, $blocks);
        }
        
        if ($request->get('redirect-to-cart') && !$request->isAjax()) {
            $this->_redirect('checkout/cart/');
            return;
        }
        
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function catalogaddAction()
    {
        $ruleIds = $this->getRequest()->get('rids');
        $itemId  = $this->getRequest()->get('item_id');

        // Check if customer is logged in.
        $customer = Mage::getSingleton('rewards/session')->getSessionCustomer();
        if (!$customer->getId()) {
            Mage::getSingleton('customer/session')->addError(
                $this->__("Please log in or sign up to apply point redemptions!")
            );
            $this->_redirect('customer/account/login');

            return;
        }

        if (!$itemId) { //If the item was not good.
            Mage::getSingleton('customer/session')->addError(
                $this->__("An item was not selected or the item selected was invalid")
            );
            $this->_redirect('checkout/cart/');

            return;
        }
        $item = Mage::getModel('sales/quote_item')->load($itemId);

        if (empty ($ruleIds) && $ruleIds != 0) {
            throw new Exception ($this->__("A valid redemption id to apply to this product was not selected."));
        }
        if (!is_array($ruleIds)) {
            $ruleList = explode(",", $ruleIds); //Turn the string of rule ids into an array
        }

        //Call function to apply the redemptions to the item
        try {
            if (Mage::getModel('rewards/redeem')->addCatalogRedemptionsToItem($item, $ruleList, $customer)) {
                $this->_getSess()->addSuccess(
                    $this->__("All requested reward redemptions were applied to the product.")
                );
            }
        } catch (Exception $e) {
            $this->_getSess()->addError($this->__($e->getMessage()));
        }

        $this->_redirect('checkout/cart/');
    }

    public function catalogremoveAction()
    {
        $ruleIds = $this->getRequest()->get('rids');
        $itemId  = $this->getRequest()->get('item_id');
        /** @var integer redemption instance id for custom redemptions (like spend X points get Y off) * */
        $redInstId = $this->getRequest()->get('inst_id');

        if (!$itemId) { //If the item was not good.
            Mage::getSingleton('customer/session')->addError(
                $this->__("An item was not selected or the item selected was invalid")
            );
            $this->_redirect('checkout/cart/');

            return;
        }
        $item = Mage::getSingleton('checkout/cart')->getQuote()->getItemById($itemId);
        if (!$item || !$item->getId()) {
            Mage::getSingleton('customer/session')->addError(
                $this->__(
                    "Your logged in session may have expired.  An item was not selected or the item selected was invalid"
                )
            );

            $this->_redirect('checkout/cart/');

            return;
        }

        //Call function to remove the redemptions to the item
        try {
            if (empty ($ruleIds) && $ruleIds != 0) {
                //customer is not logged in yet.
                throw new Exception ($this->__("A valid redemption id to apply to this product was not selected."));
            }
            if (!is_array($ruleIds)) {
                $ruleList = explode(",", $ruleIds); //Turn the string of rule ids into an array
            }

            if (Mage::getModel('rewards/redeem')->removeCatalogRedemptionsFromItem($item, $ruleList, $redInstId)) {
                $this->_getSess()->addSuccess(
                    $this->__("All requested reward redemptions were removed from the product.")
                );
            }
        } catch (Exception $e) {
            $this->_getSess()->addError($this->__($e->getMessage()));
        }

        $this->_redirect('checkout/cart/');
    }

    /**
     * Outputs a message that tells the user that their session expired.
     *
     * @return  $this
     */
    protected function refreshResponse()
    {
        $refreshJs = "";
        $refreshJs .=
            "<div class='rewards-session_expired'>" . $this->__("Your session has expired.  Please refresh the page.")
            . "</div>";

        $result = array(
            'totals'      => $refreshJs,
            'methods'     => '',
            'top_methods' => ''
        );
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($result));

        return $this;
    }
    
    protected function getChangePointErrors()
    {
        $session = Mage::getSingleton('rewards/session');
        $errors = '';
        
        if ($session->isCustomerLoggedIn() && $session->isCartOverspent()) {
            
            $pointsSpending = $session->getTotalPointsSpending();
            $pointsSpending = (array_key_exists(1, $pointsSpending)) ? $pointsSpending[1] : 0;
            
            $pointsAvailable = 0;
            $customerId = $session->getCustomerSession()->getCustomerId();
            
            if ($customerId) {
                $customer = Mage::getModel('rewards/customer')->load($customerId);
                $pointsAvailable = $customer->getUsablePointsBalance(1);
            }
            
            $errors .= '<ul id="rewards-ajax-messages" class="messages">';
            $errors .= '<li class="error-msg"><ul><li><span>';
            $errors .= Mage::helper('rewards')->__("You are trying to spend %s points on this order but you only have %s points available. You will not be able to checkout.", $pointsSpending, $pointsAvailable);
            $errors .= '</span></li></ul></li></ul>';
        }
        
        return $errors;
    }

    /**
     * This gives a tertiary check.  Since the points usage interface will
     * never go past the usable number of points, and since the discount
     * will never go past the maximum discount, we don't really care about
     * any further validation at this point... unless the customer WANTS
     * to try to spend more points.
     *
     * @param int $sp
     *
     * @return boolean
     */
    protected function isValidSpendingAmount($sp)
    {
        if ($sp < 0) {
            $customer = Mage::getSingleton('rewards/session')->getSessionCustomer();
            if ($customer->getId()) {
                Mage::helper('rewards')->logException(
                    "Customer {$customer->getEmail()} (ID: {$customer->getId(
                    )}) tried hacking the points system by forcing JS calls to spend {$sp} points!"
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Loads a quote item by it's id. If none is specified it will tried to load the quote item specified in the
     * request params, if any
     *
     * @param  int $itemId     The id of the quote item to load
     * @return Mage_Sales_Model_Quote_Item
     */
    protected function _loadValidItem($itemId = null)
    {
        if ($itemId === null) {
            $itemId = (int)$this->getRequest()->getParam('item_id');
        }
        if (!$itemId) {
            $this->_forward('noRoute');

            return null;
        }

        $item = Mage::getModel('sales/quote_item')->load($itemId);

        return $item;
    }

    /**
     * Loads a quote by it's id.  If none is specified it will tried to load the quote item specified in the request
     * params, if any
     *
     * @param  int $quoteId    The id of the quote to be loaded
     * @return Mage_Sales_Model_Quote
     */
    protected function _loadValidQuote($quoteId = null)
    {
        if ($quoteId === null) {
            $quoteId = (int)$this->getRequest()->getParam('quote_id');
        }
        if (!$quoteId) {
            $this->_forward('noRoute');

            return null;
        }

        $quote = Mage::getModel('sales/quote')->load($quoteId);

        return $quote;
    }

    protected function _loadValidRedemptions($cartRedemptions = null)
    {
        if ($cartRedemptions === null) {
            $cartRedemptions = $this->getRequest()->getParam('redem');
        }
        if (!$cartRedemptions) {
            $this->_forward('noRoute');

            return null;
        }

        return $cartRedemptions;
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
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Recreate cart blocks and return them for the ajax request
     */
    protected function fetchAjaxCartBlocks()
    {
        $cart = $this->_getCart();

        // if there are still products in the shopping cart
        if ($cart->getItemsCount()) {
            $quote = $cart->getQuote();
            $shippingAddress = $quote->getShippingAddress();
            
            $quote->collectTotals();
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();

            // Add the checkout_cart_index layout as well
            $this->loadLayout('checkout_cart_index');

            $layout = $this->getLayout();
            $totals = $layout->getBlock('checkout.cart.totals')->toHtml();
            $methods = $layout->getBlock('checkout.cart')->toHtml();
            $topMethods = $layout->getBlock('checkout.cart')->setPrefix('top_')->toHtml();

            $shippingMethods = "";
            // make sure block exists on cart page
            if ($this->getLayout()->getBlock('checkout.cart.shipping')) {
                $shippingMethods_fullTemplate = $this->getLayout()->getBlock('checkout.cart.shipping')->toHtml();
                $shippingMethods_formStartPos = strpos(
                    $shippingMethods_fullTemplate, '<form id="co-shipping-method-form"'
                );
                if ($shippingMethods_formStartPos !== false) {
                    $shippingMethods_formEndPos =
                        strpos($shippingMethods_fullTemplate, '</form>', $shippingMethods_formStartPos) + 7;
                    $shippingMethods            = substr(
                        $shippingMethods_fullTemplate, $shippingMethods_formStartPos,
                        $shippingMethods_formEndPos - $shippingMethods_formStartPos
                    );
                }
            }

            $result = array(
                'totals'           => $totals,
                'methods'          => $methods,
                'top_methods'      => $topMethods,
                'shipping_methods' => $shippingMethods,
                'error'            => false
            );
            
            $errors = $this->getChangePointErrors();
            if ($errors) {
                $result['error'] = true;
                $result['message'] = $errors;
            }
            
            return $result;
            
        } else {
            // probably the session expired.
            $this->refreshResponse();
        }
    }
    
    protected function addCartCheckboxRule()
    {
        Varien_Profiler::start("TBT_Rewards:: Add shopping cart redemption to cart");
        $ruleIds = $this->getRequest()->get('rids');
        $errorMessage = false;
        
        try {
            // Check if customer is logged in.
            $customer = Mage::getSingleton('rewards/session')->getSessionCustomer();
            if (!$customer->getId()) {
                throw new Exception(
                    $this->__("Please log in, or sign up to apply point redemptions!")
                );
            }

            if (empty ($ruleIds) && $ruleIds != 0) {
                //customer is not logged in yet.
                throw new Exception ($this->__("A valid redemption id to apply to this cart was not selected."));
            }
            if (!is_array($ruleIds)) {
                $ruleList = explode(",", $ruleIds); //Turn the string of rule ids into an array
            }

            $quote    = $this->_getQuote();
            $store    = $quote->getStore();

            //Load in a temp summary of the customers point balance, so we can check to see if the applied rules will overdraw their points
            $customerPointBalance = array();
            foreach (Mage::getSingleton('rewards/currency')->getAvailCurrencyIds() as $currencyId) {
                $customerPointBalance [$currencyId] = $customer->getUsablePointsBalance($currencyId);
            }

            $doSave = false;
            foreach ($ruleList as $ruleId) {
                $rule = Mage::helper('rewards/rule')->getSalesRule($ruleId);

                //If the rule does not apply to the cart add it to the error message
                if (array_search(( int )$ruleId, explode(',', $quote->getCartRedemptions())) === false) {
                    $message = $this->__(
                        "The rule %s does not apply to your cart.",
                        $rule->getStoreLabel($store) ? $rule->getStoreLabel($store) : $rule->getName()
                    );
                    throw new Exception($message);
                } else {
                    $rewardsSession = Mage::getSingleton('rewards/session');
                    $currencyId = $rule->getPointsCurrencyId();

                    if ($customerPointBalance[$currencyId] < $rule->getPointsAmount()) {
                        
                        $pointsSpending = $rewardsSession->getTotalPointsSpending();
                        $pointsSpending = (array_key_exists($currencyId, $pointsSpending)) ? $pointsSpending[$currencyId] : 0;
                        $pointsSpending += $rule->getPointsAmount();
                        
                        $pointsAvailable = $customer->getUsablePointsBalance($currencyId);
                        $label = $rule->getStoreLabel($store) ? $rule->getStoreLabel($store) : $rule->getName();
                                
                        $error = $this->__("You are trying to spend %s points on this order but you only have %s points available. The rule entitled '%s' was not applied to your cart.", $pointsSpending, $pointsAvailable, $label);
                        throw new Exception($error);
                    } else {
                        $applied = Mage::getModel('rewards/salesrule_list_applied')->initQuote($quote);
                        $applied->add($ruleId)->saveToQuote($quote);
                        $doSave = true;
                    }
                }
            }

            if ($doSave) {
                //@nelkaake 2/6/2010 2:45:18 PM : update shipping rates
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->save();
                $this->_getCart()->init()->save();
            }
        } catch (Exception $e) {
            $errorMessage = $this->__($e->getMessage());
        }
        
        Varien_Profiler::stop("TBT_Rewards:: Add shopping cart redemption to cart");
        $successMessage = $this->__("All requested reward redemptions were applied to your cart");
        
        return array(
            'error' => (bool)$errorMessage,
            'message' => ($errorMessage) ? $errorMessage : $successMessage,
        );
    }
    
    protected function removeCartCheckboxRule()
    {
        Varien_Profiler::start("TBT_Rewards:: remove shopping cart redemption from cart");
        $ruleIds = $this->getRequest()->get('rids');
        $errorMessage = false;
        
        try {
            if (!is_array($ruleIds)) {
                $ruleList = explode(",", $ruleIds); //Turn the string of rule ids into an array
            }

            $quote    = $this->_getQuote();
            $store    = $quote->getStore();

            $doSave = false;
            foreach ($ruleList as $ruleId) {
                $rule                   = Mage::helper('rewards/rule')->getSalesRule($ruleId);
                $appliedRedemptions    = explode(',', $quote->getAppliedRedemptions());

                //If the rule does not apply to the cart add it to the error message
                if (array_search(( int )$ruleId, $appliedRedemptions) === false) {
                    $message = $this->__(
                        "The rule named '%s' was not applied to your cart.",
                        $rule->getStoreLabel($store) ? $rule->getStoreLabel($store) : $rule->getName()
                    );
                    throw new Exception($message);
                } else {
                    // index at which the possibly removable rule id was found.
                    $applied = Mage::getModel('rewards/salesrule_list_applied')->initQuote($quote);
                    $applied->remove($ruleId)->saveToQuote($quote);
                    $doSave = true;
                }
            }

            if ($doSave) {
                //@nelkaake 2/6/2010 2:45:18 PM : update shipping rates
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->save();
                $this->_getCart()->init()->save();
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
        
        Varien_Profiler::stop("TBT_Rewards:: remove shopping cart redemption from cart");
        $successMessage = $this->__("All requested reward redemptions were removed from your cart");
                
        return array(
            'error' => (bool)$errorMessage,
            'message' => ($errorMessage) ? $errorMessage : $successMessage,
        );
    }
    
    public function fetchOrderSummaryAction() 
    {
        Mage::getSingleton('checkout/cart')->getQuote()->collectTotals();
        $this->_forward('review', 'onepage', 'checkout');
    }
}
