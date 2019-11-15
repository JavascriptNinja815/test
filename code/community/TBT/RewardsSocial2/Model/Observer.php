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
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_Observer
{
    /**
     * Behavior Rules Grid PreDisptach
     * 
     * @param Varien_Event $e
     * @event controller_action_predispatch_adminhtml_manage_special_index
     */
    public function rulesSpecialPreDispatch($e)
    {
        if (Mage::helper('rewardssocial2')->isRewardsSocialV1Enabled()) {
            $updateLink = Mage::helper("adminhtml")->getUrl('adminhtml/adminhtml_social/migration');
            $notice = Mage::helper('rewards')->__("You're using an older version of the MageRewards Social component. %sFix Now%s","<a href='{$updateLink}' title='Update Social Module'>",'</a>');
            Mage::getSingleton("adminhtml/session")->addNotice($notice);
        }
    }
    
    /**
     * Runs before the customer behavior rule is saved and checks if the
     * twitter username is set in configuration section.
     *
     * @param  Varien_Event $e
     * @return TBT_RewardsSocial2_Model_Observer
     */
    public function checkFollowSettings($e)
    {
        if (Mage::helper('rewardssocial2')->isRewardsSocialV1Enabled()) {
            return $this;
        }
        
        $data = Mage::app()->getRequest()->getPost();
        
        if (
            empty($data)
            || $data['points_conditions'] != TBT_RewardsSocial2_Model_Special_Config_TwitterFollow::ACTION_CODE
            || Mage::helper('rewardssocial2')->getTwitterUsername()
        ) {
            return $this;
        }

        $twitterFollowLink = "http://support.magerewards.com/article/1920-set-up-a-rule-for-twitter-follow-rewarding";
        $configUrl = Mage::helper("adminhtml")->getUrl('adminhtml/system_config/edit', array('section' => 'rewards'));
        
        $message = Mage::helper('rewardssocial2')->__(
            "Be sure to supply your twitter username if you're using MageRewards's default social buttons. [%sGo to Settings%s]",
            "<a href='{$configUrl}' title='Social Rewards Settings'>",
            "</a>"
        );

        Mage::getSingleton('adminhtml/session')->addNotice($message);
        return $this;
    }
}
