<?php

class Collinsharper_BrontoNotification_Model_Observer
{

    const CELEBRATE_SITE_ID = 1;

    protected $_do_not_save_customer = false;
    public function customerSaveBefore($observer)
    {

        $customer = $observer->getEvent()->getCustomer();
        if(!$customer) {
            return $this;
        }
        $this->_do_not_save_customer = true;
        $this->updateBrontoNotificationBits($customer);

    }

    public function reelSaveAfter($observer)
    {
        // some reels do not have a customer
        // get the customer and update their bits for bronto.
        //$reel = $observer->getEvent()->getReels();
        $reel = $observer->getEvent()->getObject();
        // MIGHT BE getObject
        $customerId = $reel->getCustomerId();
        if(!$customerId) {
            return $this;
        }

        $this->updateBrontoNotificationBits($customerId);
    }

    public function updateBrontoNotificationBits($customer = false)
    {

        $updated = false;
        if(!$customer) {
            return $this;
        }

        if(is_int($customer) || !is_object($customer)) {
            $customer = Mage::getModel('customer/customer')->load($customer);
        }

        if(!$customer || !$customer->getId() || $customer->getWebsiteId() != self::CELEBRATE_SITE_ID) {
            return $this;
        }

        // get all reels by cystiomer
        $reels = Mage::getModel('chreels/reels')->getCollection();
        $reels->addFieldToFilter('customer_id', $customer->getId());
        if($reels->count() && !$customer->getData('has_reels')) {
            $updated = true;
            $customer->setData('has_reels', 1);
        } else if (!$reels->count() && $customer->getData('has_reels')) {
            $updated = true;
            $customer->setData('has_reels', 0);
        }


        $hasIncompleteReels = false;
        $hasUnorderedCompleteReels = false;

        foreach($reels as $reel) {

            if(!$reel->getData('final_reel_file')) {
                // we kep what we have or get the older one...
                if(!$hasIncompleteReels || strtotime($hasIncompleteReels) > strtotime($reel->getData('updated_at'))) {
                    $hasIncompleteReels = $reel->getData('updated_at');
                }
            } else if($reel->getData('final_reel_file') && !$reel->getData('is_ordered')) {
                $hasUnorderedCompleteReels = $reel->getData('updated_at');
            }


            // is it complete and not ordered?
        }


        if($hasIncompleteReels && $customer->getData('has_unfinished_reel') != $hasIncompleteReels) {
            $customer->setData('has_unfinished_reel', $hasIncompleteReels);
            $updated = true;

        }

        if($hasUnorderedCompleteReels && $customer->getData('unordered_completed_reel') != $hasUnorderedCompleteReels) {
            $customer->setData('unordered_completed_reel', $hasUnorderedCompleteReels);
            $updated = true;

        }

        if($updated && !$this->_do_not_save_customer) {
            $customer->save();
        }
    }

    public function updateAllBits()
    {
        $customers = Mage::getModel('customer/customer')->getCollection();
        $customers->addFieldToFilter('website_id', self::CELEBRATE_SITE_ID);

        foreach($customers as $customer) {
            $this->updateBrontoNotificationBits($customer);
        }
    }
}


