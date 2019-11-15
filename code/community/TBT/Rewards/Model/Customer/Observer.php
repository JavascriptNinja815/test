<?php

class TBT_Rewards_Model_Customer_Observer extends Varien_Object
{

    /**
     * @var int
     */
    protected $oldId  = -1;

    /**
     * This is used to know if a customer model is new or not. Works by checking isObjectNew() in customerBeforeSave()
     *
     * @var string
     **/
    protected $_isNew = false;

    /**
     * Before admin customer save
     * 
     * @param Varien_Event_Observer $e
     * @event adminhtml_customer_prepare_save
     */
    public function adminCustomerPrepareSave($e)
    {
        $event = $e->getEvent();
        $customer = $event->getCustomer();
        
        $data = $event->getRequest()->getParam('rewards_points_notification');
        $emailPreference = (!empty($data));
        $customer->setRewardsPointsNotification($emailPreference);
    }
    
    /**
     * AfterLoad for customer
     * @param Varien_Event_Observer $observer
     */
    public function customerAfterLoad(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $customer = Mage::getModel('rewards/customer')->getRewardsCustomer($customer);
        return $this;
    }

    /**
     * AfterSave for customer
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function customerAfterSave(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        Mage::getSingleton('rewards/session')->setCustomer($customer);

        return $this;
    }

    /**
     * CustomerAfterCommit observes 'customer_save_commit_after'
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function customerAfterCommit($observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $rewardsCustomer = Mage::getModel('rewards/customer')->getRewardsCustomer($customer);

        //If the customer is new (hence not having an id before) get applicable rules,
        //and create a transfer for each one
        if ($this->_isNew && !$this->getCreatedTransferForNewCustomer()) {
            
            $rewardsSession = Mage::getSingleton('rewards/session');
            $rewardsSession->setRequestGroupId($rewardsCustomer->getGroupId());
            
            // making sure this will not be fired twice
            $this->setCreatedTransferForNewCustomer(true);
            $rewardsCustomer->createTransferForNewCustomer();
            
            Mage::getModel('rewards/newsletter_subscription_observer')->afterSignUpNewsletterCheck($customer);
            $rewardsSession->setCustomer($rewardsCustomer);
            
            $this->setRequiresDispatchAfterOrder(false);
            if ($this->getIsOrderBeingPlaced()) {
                $this->setRequiresDispatchAfterOrder(true);
                return $this;
            }

            $this->_dispatchCustomerCreation($rewardsCustomer);
        }

        return $this;
    }

    /**
     * BeforeSave for customer (customer_save_before)
     * @param Varien_Event_Observer $observer
     */
    public function customerBeforeSave($observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        // because 'customer_save_before' is fired twice, making sure we don't keep this as true
        $this->_isNew = false;
        if ($customer->isObjectNew()) {
            $this->_isNew = true;
        }

        return $this;
    }

    public function orderIsBeingPlaced($observer)
    {
        $this->setIsOrderBeingPlaced(true);
        return $this;
    }

    public function submitOrderSuccess($observer)
    {
        $this->setIsOrderBeingPlaced(false);

        if (!$observer->getEvent()) {
            return $this;
        }

        if (!$observer->getEvent()->getOrder()) {
            return $this;
        }

        $customer = $observer->getEvent()->getOrder()->getCustomer();
        if (!$customer) {
            return $this;
        }

        // Fix for ST-2717. Make sure we dispatchCustomerCreation if user registered on checkout.
        $quote = $observer->getEvent()->getOrder()->getQuote();
        $registeredOnCheckout = $quote ? ($quote->getCheckoutMethod (true) === Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER) : false;
        if ($this->getRequiresDispatchAfterOrder() || ($registeredOnCheckout && !$this->getHasDispatchedAfterOrder())) {
            $this->setHasDispatchedAfterOrder(true);
            $this->_dispatchCustomerCreation($customer);
        }
        return $this;
    }

    protected function _dispatchCustomerCreation($customer)
    {

        if ( Mage::helper('rewards/dispatch')->smartDispatch('rewards_customer_signup', array(
                'customer' => $customer
        )) ) {
            Mage::getSingleton('rewards/session')->triggerNewCustomerCreate($customer);
            Mage::dispatchEvent('rewards_new_customer_create', array(
                    'customer' => &$customer
            ));
        }

        return $this;
    }

    /**
     * True if the id specified is new to this customer model after a SAVE event.
     *
     * @param integer $checkId
     * @return boolean
     */
    public function isNewCustomer($checkId)
    {
        return $this->oldId != $checkId;
    }

    /**
     * Loads the customer wrapper
     * @param Mage_Customer_Model_Customer $customer
     * @return TBT_Rewards_Model_Customer_Wrapper
     */
    private function _loadCustomer(Mage_Customer_Model_Customer $customer)
    {
        return Mage::getModel('rewards/customer')->load($customer->getId());
    }

    /**
     * Sets up save for any rewards specific customer fields
     *
     * @return TBT_Rewards_Model_Customer_Observer
     */
    public function adminhtmlCustomerPrepareSave($observer)
    {
        if (!$observer->getEvent()) {
            return $this;
        }

        $request = $observer->getEvent()->getRequest();
        if (!$request) {
            return $this;
        }

        $customer = $observer->getEvent()->getCustomer();
        if (!$customer) {
            return $this;
        }

        $data = $request->getPost();

        if (isset($data['rewards_points_notification_save'])) {
            $rewardsPointsNotification = 0;
            if (isset($data['rewards_points_notification'])) {
                $rewardsPointsNotification = $data['rewards_points_notification'];
            }
            $customer->setData('rewards_points_notification', $rewardsPointsNotification);
        }

        return $this;
    }

}
