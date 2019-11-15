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
 * Social Modal Block
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Block_Modal extends TBT_RewardsSocial2_Block_Social
{
    /**
     * Create the actual modal HTML
     * @return string (HTML)
     */
    public function getModalHtml()
    {
        if (!Mage::helper('core')->isModuleEnabled('TBT_RewardsReferral')) {
            return '';
        }
        
        $referralShareBlock = $this->getLayout()->createBlock('rewardsref/customer_referral_abstract')
            ->setTemplate('rewardsref/customer/referral/affiliate.phtml')
            ->setIsInModal(true);

        $sharingButtons = $this->getLayout()->createBlock('rewardssocial2/social')
            ->setTemplate('rewardssocial2/sharing.phtml')
            ->setActionType('referral');
        
        $referralShareBlock->setChild('referral.share.widgets', $sharingButtons);
        
        return $referralShareBlock->toHtml();
    }
    
    /**
     * Fetch the modal login text
     * @return string
     */
    public function getLoginMessage()
    {
        return Mage::helper('rewardssocial2')->__(
            '%sLogin or create an account%s to be rewarded for sharing your referral link!',
            '<a href="' . Mage::getUrl('customer/account/login') . '" title="Login" />',
            '</a>'
        );
    }
}
