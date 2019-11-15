<?php

class TBT_RewardsReferral_Model_Observer_Refer extends Varien_Object implements TBT_Rewards_Model_Customer_Listener
{
    protected $_quote = null;
    protected $_order = null;

    public function recordPointsUponRegistration($observer)
    {
        try {
            $model = Mage::getModel('rewardsref/referral_signup');
            $newCustomer = $observer->getEvent()->getCustomer();
            $model->triggerEvent($newCustomer);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Observes 'sales_order_save_after' event.
     * Will create transfers for affiliate points related to the order, if it's the case.
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function recordPointsForOrderEvent($observer)
    {
        $handle = Mage::app()->getRequest()->getRouteName() 
            . '_' . Mage::app()->getRequest()->getControllerName() 
            .'_'. Mage::app()->getRequest()->getActionName();
        
        // 'sales_order_save_after' event is fired twice, so make sure we only process it once.
        if (Mage::registry('rewards_referral_record_points_for_order') || $handle === 'paypal_ipn_index') {
            return $this;
        }

        Mage::register('rewards_referral_record_points_for_order', 1);

        $orderObj     = $observer->getEvent()->getOrder();
        $this->_quote = $orderObj->getQuote();
        $orderId      = $orderObj->getId();
        $order        = Mage::getModel('rewards/sales_order')->load($orderId);

        // if user doesn't have an affiliate don't process further
        $referralModel = Mage::getModel('rewardsref/referral');
        if (!$referralModel->referralExists($order->getCustomerEmail())) {
            return $this;
        }

        $customerId = $order->getCustomerId();
        if (!$customerId) {
            // link to referral any order rule only
            $this->recordPointsGuestOrder($order);
            return $this;
        }

        $this->recordPointsUponFirstOrder($order);
        $this->recordPointsOrder($order);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return TBT_RewardsReferral_Model_Observer_Refer
     */
    public function recordPointsUponFirstOrder($order)
    {
        try {
            $model = Mage::getModel('rewardsref/referral_firstorder');
            $model->setOrder($order);
            if ($model->isSubscribed($order->getCustomerEmail()) && false == $model->isConfirmed($order->getCustomerEmail())) {
                $customer = Mage::getModel('rewards/customer')->load($order->getCustomerId());
                $model->triggerEvent($customer, $order->getId());
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Sales_Order $order
     * @return TBT_RewardsReferral_Model_Observer_Refer
     */
    public function recordPointsOrder($order)
    {

        try {
            $model = Mage::getModel('rewardsref/referral_order')
                ->setOrder($order);
            $child = Mage::getModel('rewards/customer')->load($order->getCustomerId());

            $model->triggerEvent($child, $order->getId());

            // if customers is registering on checkout and there are no points earned by the referred customer on this
            // order, reschedule for triggering for when the customer account is created & points rewarded
            $referralPoints = $model->getTotalReferralPoints($child->getId());
            if ($this->_quote && $this->_quote->getCheckoutMethod(true) == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER && $referralPoints->isEmpty()) {
                $this->_order = $order;
                $this->_getRewardsSession()->addCustomerListener($this);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Apply only for referral "Any order" rule only
     *
     * @param TBT_Rewards_Model_Sales_Order $order
     * @return TBT_RewardsReferral_Model_Observer_Refer
     */
    public function recordPointsGuestOrder($order)
    {
        try {
            $model = Mage::getModel('rewardsref/referral_guestorder');
            $model->setOrder($order);
            $emptyCustomerObj = Mage::getModel("customer/customer");
            $model->triggerEvent($emptyCustomerObj, $order->getId());
        } catch (Exception $e) {
            Mage::helper("rewards")->logException($e);
        }

        return $this;
    }

    /**
     * When a referral registers while placing an order on checkout, we need this to award points to the referrer that
     * are based on a percentage of the amount of points that the referral customer earns.
     * @param  TBT_Rewards_Model_Customers &$customer
     * @return self
     */
    public function onNewCustomerCreate(&$customer)
    {
        $this->recordPointsOrder($this->_order);
        return $this;
    }

    /**
     * Fetches the rewards session
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton('rewards/session');
    }
}
