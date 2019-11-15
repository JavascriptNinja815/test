<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * Social helper
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Helper_Data extends Mage_Core_Helper_Abstract 
{
    /**
     * @var array
     */
    protected $actionsMap = array(
        'facebook_like' => TBT_RewardsSocial2_Model_Special_Config_FacebookLike::ACTION_CODE,
        'facebook_share' => TBT_RewardsSocial2_Model_Special_Config_FacebookShare::ACTION_CODE,
        'twitter_follow' => TBT_RewardsSocial2_Model_Special_Config_TwitterFollow::ACTION_CODE,
        'twitter_tweet' => TBT_RewardsSocial2_Model_Special_Config_TwitterTweet::ACTION_CODE,
        'google_plusone' => TBT_RewardsSocial2_Model_Special_Config_GooglePlusOne::ACTION_CODE,
        'pinterest_pin' => TBT_RewardsSocial2_Model_Special_Config_PinterestPin::ACTION_CODE,
        'facebook_share_purchase' => TBT_RewardsSocial2_Model_Special_Config_PurchaseShareFacebook::ACTION_CODE,
        'twitter_tweet_purchase' => TBT_RewardsSocial2_Model_Special_Config_PurchaseShareTwitter::ACTION_CODE,
        'facebook_share_referral' => TBT_RewardsSocial2_Model_Special_Config_ReferralShare::ACTION_CODE,
        'twitter_tweet_referral' => TBT_RewardsSocial2_Model_Special_Config_ReferralShare::ACTION_CODE
    );
    
    /**
     * Check if there are any special rules for an action type
     * 
     * @param string $action
     * @return boolean
     */
    public function hasSocialRulesForAction($action)
    {
        $rules = $this->fetchApplicableRules($action);
        
        if (!$rules) {
            return false;
        }
        
        return (bool) count($rules);
    }
    
    /**
     * Fetch Rules for an action
     * 
     * @param string $action
     * @return array
     */
    public function fetchApplicableRules($action)
    {
        $action = $this->fetchBackendActionName($action);
        
        if (!$action) {
            return false;
        }
        
        return Mage::getSingleton('rewards/special_validator')->getApplicableRules($action);
    }
    
    /**
     * Fetch backend action from actionsMap
     * 
     * @param string $action
     * @return string
     */
    public function fetchBackendActionName($action)
    {
        $backendActionName = false;
        
        if (array_key_exists($action, $this->actionsMap)) {
            $backendActionName = $this->actionsMap[$action];
        }
        
        return $backendActionName;
    }
    
    /**
     * Fetch the twitter username from configuration
     * @return string
     */
    public function getTwitterUsername()
    {
        return Mage::getStoreConfig('rewards/rewardssocial2/twitter_username');
    }
    
    /**
     * Get the url of an image on the current page
     * @return string
     */
    public function getPinnableImageUrl()
    {
        // Product Page
        $product = Mage::registry('product');
        
        // Caregory Page
        if (!$product) {
            $category = Mage::registry('current_category');
            $product = $category->getProductCollection()->getFirstItem();
        }
        
        if (!$product) {
            return false;
        }
        
        return Mage::helper('catalog/image')->init($product, 'image');
    }
    
    /**
     * Predict the points that will be earned for a certain social action
     *
     * @param string $action
     * @return string | false
     */
    public function getPointsPrediction($action)
    {
        $rulesCollection = $this->fetchApplicableRules($action);
        
        $predictedPoints = array();
        foreach ($rulesCollection as $rule) {
            $currencyId = $rule->getPointsCurrencyId();
            
            if (!isset($predictedPoints[$currencyId])) {
                $predictedPoints[$currencyId] = 0;
            }
            
            $predictedPoints[$currencyId] += $rule->getPointsAmount();
        }

        if (empty($predictedPoints)) {
            return false;
        }
        
        return (string)Mage::getModel('rewards/points')->set($predictedPoints);
    }
    
    /**
     * Fetch the default tweet message
     * @param type $action
     */
    public function getTweetMessage()
    {
        return Mage::getStoreConfig('rewards/rewardssocial2/twitter_message');
    }
    
    /**
     * Fetch the url for an action type
     * 
     * @param string $action
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function fetchTargetUrl($action, $product = null)
    {
        // Referral Share
        if (strpos($action, 'referral') !== false && Mage::helper('core')->isModuleEnabled('TBT_RewardsReferral')) {
            $customer = Mage::getSingleton('rewards/session')->getCustomer();
            return (string) Mage::helper('rewardsref/url')->getCurrentUrlWithReferrer($customer);
        }
        
        // Purchase Share
        if (strpos($action, 'purchase') !== false && $product) {
            return $this->getProductUrl($product);
        }
        
        // Current Url (for basic twitter tweet and facebook share)
        return Mage::helper('core/url')->getCurrentUrl();
    }

    /**
     * Given a Product or its Id, return the appropriate URL
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductUrl($product)
    {
        if (is_numeric($product)) {
            $productId = $product;
            $product = Mage::getModel('catalog/product')->load($productId);
        }

        if (!$product || !$product->getId()) {
            return "";
        }

        $options = array('_use_rewrite' => true);
        if (!$product->getCategoryId() || !Mage::getStoreConfig('catalog/seo/product_use_categories')) {
            $options['_ignore_category'] = false;
        }

        $urlModel = $product->getUrlModel();
        return $urlModel->getUrl($product, $options);
    }
    
    /**
     * Fetch the success message for a certain action
     * 
     * @param type $action
     * @return string
     */
    public function getSuccessMessage($action)
    {
        $pointsAmount = $this->getPointsPrediction($action);
        
        $notifications = array(
            'facebook_like' => $this->__('You earned <strong>%s</strong> for liking a page on Facebook!', $pointsAmount),
            'facebook_share' => $this->__('You earned <strong>%s</strong> for sharing a page on Facebook!', $pointsAmount),
            'twitter_follow' => $this->__('You earned <strong>%s</strong> for following us on Twitter!', $pointsAmount),
            'twitter_tweet' => $this->__('You earned <strong>%s</strong> for tweeting about a page on Twitter!', $pointsAmount),
            'google_plusone' => $this->__("You earned <strong>%s</strong> for +1'ing a page on Google+!", $pointsAmount),
            'pinterest_pin' => $this->__('You earned <strong>%s</strong> for pinning a product on Pinterest!', $pointsAmount),
            'facebook_share_purchase' => $this->__('You earned <strong>%s</strong> for sharing a puchase on Facebook!', $pointsAmount),
            'twitter_tweet_purchase' => $this->__('You earned <strong>%s</strong> for tweeting a purchase on Twitter!', $pointsAmount),
            'facebook_share_referral' => $this->__('You earned <strong>%s</strong> for sharing your referral link on Facebook!', $pointsAmount),
            'twitter_tweet_referral' => $this->__('You earned <strong>%s</strong> for tweeting your referral link on Twitter!', $pointsAmount)
        );
        
        return $notifications[$action];
    }
    
    /**
     * Is TBT_Rewardssocial (first version of social rewards module) enabled
     * @return bool
     */
    public function isRewardsSocialV1Enabled()
    {
        return Mage::helper('core')->isModuleEnabled('TBT_Rewardssocial');
    }
    
    /**
     * Fetch the integration js url
     * @return string
     */
    public function getIntegrationJsUrl()
    {
        if (Mage::getStoreConfig('rewards/rewardssocial2/auto_integrate')) {
            return Mage::getStoreConfig('rewards/rewardssocial2/integration_js');
        }
        
        return '';
    }
    
    /**
     * Retrieve customer object for social action
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomerForSocialAction()
    {
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()) {
            return $customerSession->getCustomer();
        }
        
        $guestCustomerId = Mage::getSingleton('rewards/session')->getGuestCustomerId();
        if ($guestCustomerId) {
            return Mage::getModel('customer/customer')->load($guestCustomerId);
        }
        
        return Mage::getModel('customer/customer');
    }
    
    /**
     * Check if we have a customer to reward for an action
     * @return bool
     */
    public function hasCustomerToReward()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn() 
            || Mage::getSingleton('rewards/session')->getGuestCustomerId();
    }

    /**
     * Get current url with or without SID
     * @param boolean $includeSID
     * @return string
     */
    public function getCurrentUrl($includeSID = true)
    {
        $requestQuery = Mage::app()->getRequest()->getQuery();

        if (!$includeSID && isset($requestQuery['___SID'])) {
            unset($requestQuery['___SID']);
        }

        $currentUrl = Mage::getUrl(
            '*/*/*/',
            array(
                '_current' => true,
                '_use_rewrite' => true,
                '_query' => $requestQuery
            )
        );

        return $currentUrl;
    }

    /**
     * Check if null or empty string
     * @param string $str
     * @return boolean
     */
    public function isNullOrEmptyString($str)
    {
        return (!isset($str) || trim($str)==='');
    }
}
