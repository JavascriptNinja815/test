<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
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
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Rewardssocial Action Model
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_Action extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('rewardssocial2/action');
    }
    
    /**
     * Validate daily, weekly, monthly, yearly and lifetime social request 
     * limits. Will return an error message if problems where found or
     * false otherwise.
     * 
     * @param int $customerId
     * @return string (error) | false (no error)
     */
    public function validateRequestLimit($customerId)
    {
        $resource = $this->getResource();

        // Daily Limit Check
        if (!$resource->isWithinDailyLimit($customerId)) {
            return Mage::helper('rewardssocial2')->__("You've exceeded the daily award limit for social interactions.");
        }
        
        // Weekly Limit Check
        if (!$resource->isWithinWeeklyLimit($customerId)) {
            return Mage::helper('rewardssocial2')->__("You've exceeded the weekly award limit for social interactions.");
        }
        
        // Monthly Limit Check
        if (!$resource->isWithinMonthlyLimit($customerId)) {
            return Mage::helper('rewardssocial2')->__("You've exceeded the monthly award limit for social interactions.");
        }
        
        // Yearly Limit Check
        if (!$resource->isWithinYearlyLimit($customerId)) {
            return Mage::helper('rewardssocial2')->__("You've exceeded the yearly award limit for social interactions.");
        }
        
        // Lifetime Limit Check
        if (!$resource->isWithinLifetimeLimit($customerId)) {
            return Mage::helper('rewardssocial2')->__("You've exceeded your award allocation for social interactions.");
        }

        // No problems found
        return false;
    }
    
    /**
     * Set extra data depending on action type
     * 
     * @param array $data
     */
    public function genericExtraSetter($data)
    {
        switch ($this->getAction()) {
            case 'facebook_like':
            case 'twitter_tweet':
            case 'google_plusone':
            case 'pinterest_pin':
            case 'facebook_share':
                $url = $data['url'];
                $this->setExtra($url);
                
                break;
            case 'facebook_share_purchase':
            case 'twitter_tweet_purchase':
                $extra = array(
                    'product' => $data['product'],
                    'order' => $data['order']
                );
                
                $this->setExtra(json_encode($extra));
                
                break;
            case 'twitter_follow':
            case 'facebook_share_referral':
            case 'twitter_tweet_referral':
                break;
        }
        
        return $this;
    }
    
    /**
     * Before Save processor
     * @return \TBT_RewardsSocial2_Model_Action
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        
        if (!$this->getId() && !$this->getCreatedAt()) {
            $this->setCreatedAt(now());
        }
        
        $this->setUpdatedAt(now());
        
        return $this;
    }
}
