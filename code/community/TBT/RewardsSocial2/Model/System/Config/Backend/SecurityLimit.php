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
 */

/**
 * Security Limit Backend Model
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_System_Config_Backend_SecurityLimit extends Mage_Core_Model_Config_Data
{
    public function _beforeSave()
    {
        $value = $this->getValue();
        
        if ($value && !preg_match('/^[0-9]\d*$/', $value)) {
            $message = Mage::helper('rewardssocial2')->__('Invalid Security Limit. Only positive integers are allowed.');
            Mage::throwException($message);
        }
        
        $fieldsetData = $this->getFieldsetData();
        
        switch ($this->getField()) {
            case 'daily_limit':
                if ($value && $value > $fieldsetData['weekly_limit']) {
                    Mage::throwException(
                        Mage::helper('rewardssocial2')
                            ->__('Invalid Social Rewards `Daily Limit` value. The value must be lower than or equal to the Social Rewards `Weekly Limit`.')
                    );
                }
                break;
            case 'weekly_limit':
                if ($value && $value > $fieldsetData['monthly_limit']) {
                    Mage::throwException(
                        Mage::helper('rewardssocial2')
                            ->__('Invalid Social Rewards `Weekly Limit` value. The value must be lower than or equal to the Social Rewards `Monthly Limit`.')
                    );
                }
                break;
            case 'monthly_limit':
                if ($value && $value > $fieldsetData['yearly_limit']) {
                    Mage::throwException(
                        Mage::helper('rewardssocial2')
                            ->__('Invalid Social Rewards `Monthly Limit` value. The value must be lower than or equal to the Social Rewards `Yearly Limit`.')
                    );
                }
                break;
            case 'yearly_limit':
                if ($value && $value > $fieldsetData['lifetime_limit']) {
                    Mage::throwException(
                        Mage::helper('rewardssocial2')
                            ->__('Invalid Social Rewards `Yearly Limit` value. The value must be lower than or equal to the Social Rewards `Lifetime Limit`.')
                    );
                }
                break;
        }
        
        return parent::_beforeSave();
    }
}
