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
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * Catalog Social Buttons
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_System_Config_Source_Catalog  
{
    const FACEBOOK_LIKE = 1;
    const FACEBOOK_SHARE = 2;
    const TWITTER_TWEET = 3;
    const TWITTER_FOLLOW = 4;
    const PINTEREST_PIN = 5;
    const GOOGLE_PLUSONE = 6;
    const REFER_FRIENDS = 7;
    
    /**
     * Retrieve Catalog Social Buttons Option array
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::FACEBOOK_LIKE,
                'label' => Mage::helper('rewardssocial2')->__('Facebook Like')
            ),
            array(
                'value' => self::FACEBOOK_SHARE,
                'label' => Mage::helper('rewardssocial2')->__('Facebook Share')
            ),
            array(
                'value' => self::TWITTER_TWEET,
                'label' => Mage::helper('rewardssocial2')->__('Twitter Tweet')
            ),
            array(
                'value' => self::TWITTER_FOLLOW,
                'label' => Mage::helper('rewardssocial2')->__('Twitter Follow')
            ),
            array(
                'value' => self::PINTEREST_PIN,
                'label' => Mage::helper('rewardssocial2')->__('Pinterest Pin')
            ),
            array(
                'value' => self::GOOGLE_PLUSONE,
                'label' => Mage::helper('rewardssocial2')->__('Google +1')
            ),
            array(
                'value' => self::REFER_FRIENDS,
                'label' => Mage::helper('rewardssocial2')->__('Refer Friends')
            ),
        );
    }
}
