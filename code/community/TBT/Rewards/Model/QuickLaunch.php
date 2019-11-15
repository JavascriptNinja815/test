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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Quick Launch Model
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_QuickLaunch
{
    public $mainGoal = array(
        1 => 'acquisition',
        2 => 'retention',
        3 => 'retention & acquisition'
    );
    
    public $averageOrderValue = array(
        1 => '$0 - $100',
        2 => '$100 - $200',
        3 => '$200 - $300',
        4 => '$300 - $500',
        5 => '$500 - $1000',
        6 => '$1000+'
    );
    
    public $priceMargin = array(
        1 => '0% - 30%',
        2 => '30% - 60',
        3 => '60%+'
    );
            
    public function isAccountConnected()
    {
        return Mage::getStoreConfig('rewards/platform/is_connected');
    }
    
    public function hasLoyaltyProgram() 
    {
        return Mage::getStoreConfig('rewards/quickLaunch/loyaltyProgramData');
    }
    
    public function connectAccount($data)
    {
        if (!isset($data['username']) || !isset($data['password'])) {
            return false;
        }
        
        $devMode = isset($data['isDevMode']);
        
        Mage::register('st_disable_rule_save_tracking', true);
        Mage::getConfig()->saveConfig('rewards/platform/dev_mode', $devMode);
        Mage::getConfig()->cleanCache();

        Mage::helper('rewards/platform')->connectWithPlatformAccount(
            $data['username'], 
            $data['password'], 
            $devMode
        );
        
        Mage::helper('rewards/tracking')->track(TBT_Rewards_Helper_Tracking::EVENT_CONNECT, array('triggered_from' => 'quicklaunch'));
        return true;
    }
    
    public function saveSettings($data)
    {
        $hasErrors = false;
        $helper = Mage::helper('rewards');
        $session = Mage::getSingleton('core/session');
        
        if (empty($data['main-goal'])) {
            $hasErrors = true;
            $session->addError($helper->__("Please select the main goal for your loyalty program."));
        }
        
        if (empty($data['average-order-value'])) {
            $hasErrors = true;
            $session->addError($helper->__("Please specify your average order value so we can suggest a suggest a more appropriate loyalty program for you."));
        }
        
        if (empty($data['price-margin'])) {
            $hasErrors = true;
            $session->addError($helper->__("Please specify your average product price margin so we can suggest a suggest a more appropriate loyalty program for you."));
        }

        if ($hasErrors) {
            return false;
        }
        
        Mage::getConfig()->saveConfig('rewards/quickLaunch/loyaltyProgramData', Mage::helper('rewards/serializer')->serializeData($data));
        Mage::getConfig()->cleanCache();
        
        return true;
    }
    
    public function launchProgram($data)
    {
        Mage::register('st_disable_rule_save_tracking', true);
        $helper = Mage::helper('rewards');
        $loyaltyProgramSettings = Mage::getStoreConfig('rewards/quickLaunch/loyaltyProgramData');
        $loyaltyProgramSettings = Mage::helper('rewards/serializer')->unserializeData($loyaltyProgramSettings);
        
        $websites = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $websites[] = $website->getId();
        }
        
        $customerGroups = Mage::getModel('customer/group')->getCollection()->getAllIds();
        
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        
        // Saving Points Caption
        if (!empty($loyaltyProgramSettings['points-caption'])) {
            $currency = Mage::getModel('rewards/currency')->load($currencyId);
            
            if ($currency->getId()) {
                $currency->setCaption($loyaltyProgramSettings['points-caption']);
                $currency->save();
            }
        }
        
        // Spending Rule
        if (!empty($data['spending-points'])) {
            $spendingRuleData = array(
                'name' => $helper->__("Quick-Launch Rule - Spend %s points for each %s0.01 discount", $data['spending-points'], $currencySymbol),
                'is_active' => 1,
                'website_ids' => $websites,
                'customer_group_ids' => $customerGroups,
                'points_action' => 'discount_by_points_spent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['spending-points'],
                'points_discount_action' => 'cart_fixed',
                'points_discount_amount' => 0.01,
                'stop_rules_processing' => 0
            );
            
            $model = Mage::getModel('rewards/salesrule_rule');
            $model->loadPost($spendingRuleData);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Earning Rule
        if (!empty($data['earning-points'])) {
            $earningRuleData = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s point for every %s1 spent", $data['earning-points'], $currencySymbol),
                'is_active' => 1,
                'website_ids' => $websites,
                'customer_group_ids' => $customerGroups,
                'points_action' => 'give_by_amount_spent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['earning-points'],
                'points_qty_step' => 0,
                'points_max_qty' => 0,
                'stop_rules_processing' => 0
            );
            
            $model = Mage::getModel('rewards/salesrule_rule');
            $model->loadPost($earningRuleData);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Sign Up Bonus
        if (!empty($data['sign-up'])) {
            $signUpData = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s points for Signing Up", $data['sign-up']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'customer_sign_up',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['sign-up'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($signUpData);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Reward reviews
        if (!empty($data['review'])) {
            $reviewRuleData = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s points for writing a review", $data['review']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'customer_writes_review',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['review'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($reviewRuleData);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Reward newsletter subscription
        if (!empty($data['newsletter'])) {
            $newsletterRuleData = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s points for Subscribing to the Newsletter", $data['newsletter']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'customer_newsletter',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['newsletter'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($newsletterRuleData);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Social media shares and actions
        if (!empty($data['social-media'])) {
            $socialActions = array(
                'facebook_like' => 'liking the store on Facebook', 
                'twitter_tweet' => 'tweeting on Twitter', 
                'google_plusOne' => '+1-ing on Google+',
                'pinterest_pin' => 'pining an image on Pinterest',
                'twitter_follow' => 'following us on twitter',
                'facebook_share' => 'sharing a link on Facebook',
                'purchase_share_facebook' => 'sharing a purchase on Facebook',
                'purchase_share_twitter' => 'sharing a purchase on Twitter'
            );
            
            foreach ($socialActions as $socialAction => $title) {
                $socialRuleData = array(
                    'name' => $helper->__("Quick-Launch Rule - Earn %s points for {$title}", $data['social-media']),
                    'is_active' => 1,
                    'website_ids' => implode(',', $websites),
                    'customer_group_ids' => implode(',', $customerGroups),
                    'points_conditions' => "social_{$socialAction}",
                    'points_action' => 'grant_points',
                    'simple_action' => 'by_percent',
                    'points_currency_id' => $currencyId,
                    'points_amount' => $data['social-media'],
                    'is_onhold_enabled' => 0,
                    'onhold_duration' => 0,
                    'from_date' => '',
                    'to_date' => ''
                );

                $model = Mage::getModel('rewards/special');
                $model->loadPost($socialRuleData);
                Mage::getSingleton('adminhtml/session')->setPageData($model->getData());

                $model->save();
                Mage::getSingleton('adminhtml/session')->setPageData(false);
            }
        }
        
        // Customer shares their referral link
        if (!empty($data['referral-link'])) {
            $referralLinkSharingRule = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s points for sharing your referral link", $data['referral-link']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'social_referral_share',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['referral-link'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($referralLinkSharingRule);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Referral makes first order
        if (!empty($data['referral-first-order'])) {
            $referralFirstOrderRule = array(
                'name' => $helper->__("Quick-Launch Rule - Referral First Order - %s points", $data['referral-first-order']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'customer_referral_firstorder',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['referral-first-order'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($referralFirstOrderRule);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Customer birthday
        if (!empty($data['customer-birthday'])) {
            $birthdayRule = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s points on your Birthday", $data['customer-birthday']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'customer_birthday',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['customer-birthday'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($birthdayRule);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            
            $birthdayRule['rewards_special_id'] = $model->getId();
            $object = new Varien_Object();
            $object->setData($birthdayRule);
            Mage::getSingleton('tbtmilestone/adapter_special')->afterSaveAction($object);
            
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        // Yearly anniversary
        if (!empty($data['yearly-anniversary-points']) && !empty($data['yearly-anniversary-cicles'])) {
            for ($i= 1; $i <= $data['yearly-anniversary-cicles']; $i++) {
                $days = 365 * $i;
                $anniversaryRule = array(
                    'name' => $helper->__("Quick-Launch Rule - Earn %s points after %s days of membership", $data['yearly-anniversary-points'], $days),
                    'is_active' => 1,
                    'website_ids' => implode(',', $websites),
                    'customer_group_ids' => implode(',', $customerGroups),
                    'points_conditions' => 'tbtmilestone_membership',
                    'points_action' => 'grant_points',
                    'simple_action' => 'by_percent',
                    'points_currency_id' => $currencyId,
                    'points_amount' => $data['yearly-anniversary-points'],
                    'tbtmilestone_membership' => $days,
                    'is_onhold_enabled' => 0,
                    'onhold_duration' => 0,
                    'from_date' => '',
                    'to_date' => ''
                );

                $model = Mage::getModel('rewards/special');
                $model->loadPost($anniversaryRule);
                Mage::getSingleton('adminhtml/session')->setPageData($model->getData());

                $model->save();

                $anniversaryRule['rewards_special_id'] = $model->getId();
                $object = new Varien_Object();
                $object->setData($anniversaryRule);
                Mage::getSingleton('tbtmilestone/adapter_special')->afterSaveAction($object);

                Mage::getSingleton('adminhtml/session')->setPageData(false);
            }
        }
        
        // Inactivity rule
        if (!empty($data['inactivity-points']) && !empty($data['inactivity-days'])) {
            $inactivityRule = array(
                'name' => $helper->__("Quick-Launch Rule - Earn %s points after %s days of inactivity", $data['inactivity-points'], $data['inactivity-days']),
                'is_active' => 1,
                'website_ids' => implode(',', $websites),
                'customer_group_ids' => implode(',', $customerGroups),
                'points_conditions' => 'tbtmilestone_inactivity',
                'points_action' => 'grant_points',
                'simple_action' => 'by_percent',
                'points_currency_id' => $currencyId,
                'points_amount' => $data['inactivity-points'],
                'tbtmilestone_inactivity' => $data['inactivity-days'],
                'is_onhold_enabled' => 0,
                'onhold_duration' => 0,
                'from_date' => '',
                'to_date' => ''
            );
            
            $model = Mage::getModel('rewards/special');
            $model->loadPost($inactivityRule);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
            
            $model->save();
            
            $inactivityRule['rewards_special_id'] = $model->getId();
            $object = new Varien_Object();
            $object->setData($inactivityRule);
            Mage::getSingleton('tbtmilestone/adapter_special')->afterSaveAction($object);
            
            Mage::getSingleton('adminhtml/session')->setPageData(false);
        }
        
        Mage::getConfig()->saveConfig('rewards/reviews_and_tags/weekly_review_limit', 1);
        Mage::getConfig()->saveConfig('rewards/rewardssocial2/lifetime_limit', 10);
        
        Mage::getConfig()->saveConfig('rewards/rewardssocial2/weekly_limit', '');
        Mage::getConfig()->saveConfig('rewards/rewardssocial2/monthly_limit', '');
        Mage::getConfig()->saveConfig('rewards/rewardssocial2/yearly_limit', '');
        
        Mage::getConfig()->cleanCache();
        
        $this->sendTrackingData($loyaltyProgramSettings);  
        return true;
    }
    
    /**
     * Send quick launch related tracking data
     * 
     * @param array $loyaltyProgramSettings
     * @return \TBT_Rewards_Model_QuickLaunch
     */
    protected function sendTrackingData($loyaltyProgramSettings)
    {
        $trackingData = array(
            'program_name' => (isset($loyaltyProgramSettings['program-name'])) ? $loyaltyProgramSettings['program-name'] : null,
            'points_caption' => (isset($loyaltyProgramSettings['points-caption'])) ? $loyaltyProgramSettings['points-caption'] : null,
            'campaign_goal' => $this->mainGoal[$loyaltyProgramSettings['main-goal']],
            'average_order_value' => $this->averageOrderValue[$loyaltyProgramSettings['average-order-value']],
            'average_product_price_margin' => $this->priceMargin[$loyaltyProgramSettings['price-margin']],
        );
        
        Mage::helper('rewards/tracking')->track(TBT_Rewards_Helper_Tracking::EVENT_QUICK_LAUNCH, $trackingData);
        return $this;
    }
}

