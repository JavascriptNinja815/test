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
 * Rewardssocial Transfer Model
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_Transfer extends TBT_Rewards_Model_Transfer
{
    /**
     * Reason ID mapping
     * @var array
     */
    protected $reasonsMap = array(
        'facebook_like' => 'social_facebook_like',
        'facebook_share' => 'social_facebook_share',
        'twitter_follow' => 'social_twitter_follow',
        'twitter_tweet' => 'social_twitter_tweet',
        'google_plusone' => 'social_google_plusone',
        'pinterest_pin' => 'social_pinterest_pin',
        'facebook_share_purchase' => 'social_facebook_share_purchase',
        'twitter_tweet_purchase' => 'social_twitter_tweet_purchase',
        'facebook_share_referral' => 'social_referral_share',
        'twitter_tweet_referral' => 'social_referral_share'
    );
    
    /**
     * Configuration paths for initial transfer status
     * @var array
     */
    protected $initialStatusPaths = array(
        'facebook_like' => 'rewards/InitialTransferStatus/AfterFacebookLike',
        'facebook_share' => 'rewards/InitialTransferStatus/AfterFacebookProductShare',
        'twitter_follow' => 'rewards/InitialTransferStatus/AfterTwitterFollow',
        'twitter_tweet' => 'rewards/InitialTransferStatus/AfterTwitterTweet',
        'google_plusone' => 'rewards/InitialTransferStatus/AfterGooglePlusOne',
        'pinterest_pin' => 'rewards/InitialTransferStatus/AfterPinterestPin',
        'facebook_share_purchase' => 'rewards/InitialTransferStatus/afterPurchaseShareOnFacebook',
        'twitter_tweet_purchase' => 'rewards/InitialTransferStatus/afterPurchaseShareOnTwitter',
        'facebook_share_referral' => 'rewards/InitialTransferStatus/AfterReferralShare',
        'twitter_tweet_referral' => 'rewards/InitialTransferStatus/AfterReferralShare'
    );
    
    /**
     * Configuration paths for transfer comments
     * @var array
     */
    protected $transferCommentPaths = array(
        'facebook_like' => 'rewards/transferComments/facebookLike',
        'facebook_share' => 'rewards/transferComments/facebookProductShare',
        'twitter_follow' => 'rewards/transferComments/twitterFollow',
        'twitter_tweet' => 'rewards/transferComments/twitterTweet',
        'google_plusone' => 'rewards/transferComments/googlePlusOne',
        'pinterest_pin' => 'rewards/transferComments/pinterestPin',
        'facebook_share_purchase' => 'rewards/transferComments/purchaseShareOnFacebook',
        'twitter_tweet_purchase' => 'rewards/transferComments/purchaseShareOnTwitter',
        'facebook_share_referral' => 'rewards/transferComments/referralShare',
        'twitter_tweet_referral' => 'rewards/transferComments/referralShare'
    );
    
    /**
     * Create all necessary transfers for a social action
     * 
     * @param TBT_RewardsSocial2_Model_Action $socialAction
     */
    public function initiateTransfers($socialAction)
    {
        $helper = Mage::helper('rewardssocial2');
        
        $action = $socialAction->getAction();
        $customer = $helper->getCustomerForSocialAction();
        $rules = $helper->fetchApplicableRules($action);
        
        foreach ($rules as $rule) {
            $this->createSocialTransfer($customer, $rule, $socialAction);
        }
    }
    
    /**
     * Create all social transfers for a certain rule
     * 
     * @param Mage_Customer_Model_Customer $customer
     * @param TBT_Rewards_Model_Salesrule_Rule $rule
     * @param TBT_RewardsSocial2_Model_Action $socialAction
     * @return boolean | TBT_RewardsSocial2_Model_Transfer
     */
    public function createSocialTransfer($customer, $rule, $socialAction)
    {
        $pointsAmount = $rule->getPointsAmount();
        $ruleId = $rule->getId();
        
        $store = $customer->getStore();
        $action = $socialAction->getAction();

        // Inititate transfer
        $transfer = $this->initTransfer($pointsAmount, $ruleId, $customer->getId(), true);

        if (!$transfer) {
            return false;
        }

        // Get on-hold initial status override
        if ($rule->getOnholdDuration() > 0) {
            $transfer->setEffectiveStart(date('Y-m-d H:i:s', strtotime("+{$rule->getOnholdDuration()} days")))
                ->setStatusId(null, TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME);
        } else {
            // Get the default starting status
            $initialStatus = $this->getInitialTransferStatus($action, $store);
            if (!$transfer->setStatusId(null, $initialStatus)) {
                return false;
            }
        }

        // Fetch transfer comments
        $initialTransferMessage = $this->getTransferComments($action, $store);
        $comments = Mage::helper('rewardssocial2')->__($initialTransferMessage);
        $actionId = $socialAction->getId();
        
        // Set Additional Data
        $this->setReferenceId($actionId)
            ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId($this->reasonsMap[$action]))
            ->setActionId($actionId)
            ->setComments($comments)
            ->setCustomerId($customer->getId())
            ->save();

        return $this;
    }
    
    /**
     * Fetch the initial status for a social action
     * 
     * @param string $action
     * @param int $store
     * @return string|boolean
     */
    public function getInitialTransferStatus($action, $store = null)
    {
        if (isset($this->initialStatusPaths[$action])) {
            return Mage::getStoreConfig($this->initialStatusPaths[$action], $store);
        }
        
        return false;
    }
    
    /**
     * Fetch transfer comments for a social action
     * 
     * @param string $action
     * @param int $store
     * @return string|boolean
     */
    public function getTransferComments($action, $store = null)
    {
        if (isset($this->transferCommentPaths[$action])) {
            return Mage::getStoreConfig($this->transferCommentPaths[$action], $store);
        }
        
        return false;
    }
    
    public function loadByCustomerAndReason($customerId, $reasonId)
    {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', $reasonId)
            ->addFieldToFilter('customer_id', $customerId)
            ->getFirstItem();
    }
    
    public function loadFromData($customerId, $referenceId, $reasonId)
    {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', $reasonId)
            ->addFieldToFilter('reference_id', $referenceId)
            ->addFieldToFilter('customer_id', $customerId)
            ->getFirstItem();
    }
}
