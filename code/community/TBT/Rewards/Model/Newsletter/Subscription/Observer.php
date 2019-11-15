<?php

class TBT_Rewards_Model_Newsletter_Subscription_Observer extends Varien_Object 
{
    /**
     * @var TBT_Rewards_Model_Newsletter_Subscriber_Wrapper
     */
    protected $_rsubscriber = null;
    protected $_wasSubscribed = false;
    
    /**
     * An array of customer's id already rewarded
     * @var array 
     */
    protected $_rewardedCustomersHistory = array();

    /**
     * Before subscription save event
     * @param Varien_Event_Observer $o
     */
    public function beforeSaveSubscription(Varien_Event_Observer $o)
    {
        $subscriberInst = ($o->getEvent()->hasObject()) ? $o->getEvent()->getObject() : $o->getEvent()->getDataObject();
        
        if (!($subscriberInst instanceof Mage_Newsletter_Model_Subscriber)) {
            return $this;
        }
        
        $this->_rsubscriber = Mage::getModel('rewards/newsletter_subscriber_wrapper')->wrap($subscriberInst);
        $this->_wasSubscribed = $subscriberInst->isSubscribed();
        return $this;
    }

    /**
     * After subscription save event
     * @param Varien_Event_Observer $o
     */
    public function afterSaveSubscription(Varien_Event_Observer $o)
    {
        $newSubscriberInst = ($o->getEvent()->hasObject()) ? $o->getEvent()->getObject() : $o->getEvent()->getDataObject();
        
        if (!($newSubscriberInst instanceof Mage_Newsletter_Model_Subscriber)) {
            return $this;
        }
        
        $newRSubscriberInst = Mage::getModel('rewards/newsletter_subscriber_wrapper')->wrap($newSubscriberInst);
        $customerId = $newRSubscriberInst->getCustomer()->getId();
        if ($customerId != $this->_rsubscriber->getCustomer()->getId()) {
            return $this;
        }
        
        if (in_array($customerId, $this->_rewardedCustomersHistory)) {
            return $this;
        }
		
        if (($newSubscriberInst->isSubscribed() && ! $this->_wasSubscribed) || ($newSubscriberInst->isSubscribed () && $this->_wasSubscribed && $newSubscriberInst->getIsStatusChanged())) {
            Mage::dispatchEvent('rewards_newsletter_new_subscription', array('subscriber' => $newRSubscriberInst->getSubscriber()));
            $this->initReward($newRSubscriberInst->getSubscriber());
            $this->_rewardedCustomersHistory[] = $customerId;
        }
		
        return $this;
    }
	
    /**
     * Loops through each Special rule. If it applies, create a new pending transfer.
     */
    public function initReward(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        if (Mage::registry('st_newsletter_init')) {
            return $this;
        }
        
        Mage::register('st_newsletter_init', true);
        $rsubscriber = Mage::getModel('rewards/newsletter_subscriber_wrapper')->wrap($subscriber);

        try {            
            if (!$rsubscriber->customerHasPointsForNewsletter()) {
                $ruleCollection = $this->_getNewsletterValidator()->getApplicableRulesOnNewsletter();
                foreach ($ruleCollection as $rule) {
                    if (!$rule->getId()) {
                        continue;
                    }

                    try {
                        $transfer = Mage::getModel('rewards/newsletter_subscription_transfer');
                        $isTransferSuccessful = $transfer->createNewsletterSubscriptionPoints($rsubscriber, $rule);

                        if ($isTransferSuccessful) {
                            $pointsForSigningUp = Mage::getModel ( 'rewards/points' )->set ( $rule );
                            Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewards')->__('You received %s for signing up to a newsletter', $pointsForSigningUp));
                        }
                    } catch (Exception $ex) {
                        Mage::logException($ex);
                        Mage::getSingleton('core/session')->addError($ex->getMessage());
                    }
                }
            } else {
                Mage::getSingleton('core/session')->addNotice(Mage::helper('rewards')->__("You've already received points for signing up to this newsletter in the past, so you won't get any this time."));
            }
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(Mage::helper('rewards')->__('Could not interface with customer rewards system.'));
        }
    }
    
    /**
     * Check if customer already signed up to the mailing list and award points if necessary
     * 
     * @param Mage_Customer_Model_Customer $customer
     * @return $this
     */
    public function afterSignUpNewsletterCheck(Mage_Customer_Model_Customer $customer)
    {
        if (!$customer || !$customer->getId()) {
            return $this;
        }
        
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());
        if ($subscriber && $subscriber->isSubscribed()) {
             $this->initReward($subscriber);
        }
        
        return $this;
    }

    /**
     * Fetches the rewards special validator singleton
     * @return TBT_Rewards_Model_Newsletter_Validator
     */
    protected function _getNewsletterValidator()
    {
        return Mage::getSingleton('rewards/newsletter_validator');
    }
}

