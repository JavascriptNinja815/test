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
 * Social Block
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Block_Social extends Mage_Core_Block_Template
{
    /**
     * Current route
     * @var string 
     */
    protected $routeName;
    
    /**
     * Available Buttons for this page
     * @var array
     */
    protected $availableButtons = null;
    
    /**
     * Available sharing buttons for this page (referral and purchase)
     * @var array
     */
    protected $availableSharingButtons = null;
    
    /**
     * Fetch the current route
     * @return string
     */
    public function getRouteName()
    {
        if (!$this->routeName) {
            $this->routeName = Mage::app()->getRequest()->getRequestedRouteName();
        }
        
        return $this->routeName;
    }
    
    /**
     * Fetch the current theme
     * @return string
     */
    public function getTheme()
    {
        $theme = $this->getData('theme');
        return ($theme) ? $theme : 'default';
    }
    
    /**
     * Get available social buttons for the current request
     * @return array
     */
    public function getAvailableSocialButtons()
    {
        if (is_null($this->availableButtons)) {
            $routeName = $this->getRouteName();

            $buttons = false;
            if ($routeName === 'cms'){
                $buttons = Mage::getStoreConfig('rewards/rewardssocial2/homepage_buttons');
                
            } elseif ( $routeName === 'catalog') {
                $buttons = Mage::getStoreConfig('rewards/rewardssocial2/catalog_buttons');
            }
            
            $this->availableButtons = ($buttons) ? explode(',', $buttons) : array();
        }
        
        return $this->availableButtons;
    }
    
    /**
     * Check if a social button is enabled
     * 
     * @param string $button
     * @return boolean
     */
    public function isButtonEnabled($button)
    {
        $button = strtoupper($button);
        $availableButtons = $this->getAvailableSocialButtons();
        
        if ($this->getRouteName() === 'cms' && $button !== 'PINTEREST_PIN') {
            return in_array(constant('TBT_RewardsSocial2_Model_System_Config_Source_Homepage::' . $button), $availableButtons);
        } 
        
        if ($this->getRouteName() === 'catalog') {
            return in_array(constant('TBT_RewardsSocial2_Model_System_Config_Source_Catalog::' . $button), $availableButtons);
        }

        return false;
    }
    
    /**
     * Get available sharing social buttons for the current request (referral & purchase)
     * @return array
     */
    public function getAvailableSharingButtons()
    {
        if (is_null($this->availableSharingButtons)) {
            
            $buttons = false;
            
            if ($this->getActionType() === 'referral') {
                $buttons = Mage::getStoreConfig('rewards/rewardssocial2/referral_buttons');
            } elseif ($this->getActionType() === 'purchase') {
                $buttons = Mage::getStoreConfig('rewards/rewardssocial2/purchase_buttons');
            }

            $this->availableSharingButtons = ($buttons) ? explode(',', $buttons) : array();
        }
        
        return $this->availableSharingButtons;
    }
    
    /**
     * Check if a sharing social button is enabled (referral & purchase)
     * 
     * @param string $button
     * @return boolean
     */
    public function isSharingButtonEnabled($button)
    {
        $button = strtoupper($button);
        $availableButtons = $this->getAvailableSharingButtons();
        
        if ($this->getActionType() === 'referral') {
            return in_array(constant('TBT_RewardsSocial2_Model_System_Config_Source_Refer::' . $button), $availableButtons);
        }
        
        if ($this->getActionType() === 'purchase') {
            return in_array(constant('TBT_RewardsSocial2_Model_System_Config_Source_Purchase::' . $button), $availableButtons);
        }
        
        return true;
    }
    
    /**
     * Should we render the social buttons?
     * @return bool
     */
    public function areButtonsEnabled()
    {
        return !Mage::helper('rewardssocial2')->isRewardsSocialV1Enabled();
    }
}
