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
 * Quick Launch Block
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_QuickLaunch extends Mage_Core_Block_Template 
{
    const DEFAULT_THEME = 'gold';
    
    protected function _construct() 
    {
        $this->setTemplate('rewards/quickLaunch/index.phtml');
    }
    
    public function getCurrencySymbol()
    {
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        return Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
    }
    
    public function isAccountConnected()
    {
        return !Mage::getStoreConfig('rewards/platform/is_connected');
    }
    
    public function getStepHtml()
    {
        $step = $this->getStep();
        $data = Mage::helper('rewards/quickLaunch')->getData($step);
        
        return $this->getLayout()
            ->createBlock('rewards/quickLaunch')
            ->setData($data)
            ->setTemplate($data['template'])
            ->toHtml(); 
    }
    
    public function getProgramData()
    {
        $data = Mage::getStoreConfig('rewards/quickLaunch/loyaltyProgramData');
        return Mage::helper('rewards/serializer')->unserializeData($data);
    }
    
    public function getCustomizedLoyaltyProgramData()
    {
        $data = $this->getProgramData();
        
        $programTypeText = '';
        switch ($data['main-goal']) {
            case (1):
                $programTypeText = 'generate more customer acquisition.';
                break;
            case (2):
                $programTypeText = 'generate more orders and customers.';
                break;
            case (3):
                $programTypeText = 'generate repeat orders from existing customers.';
                break;
        }
        
        $loyaltyProgramData = array(
            'program-type' => $programTypeText,
            'earnings' => $data['price-margin'],
            'spendings' => 1
        );
        
        switch ($data['main-goal']) {
            case (1):
                $loyaltyProgramData['sign-up'] = 100;
                $loyaltyProgramData['social-actions'] = 10;
                $loyaltyProgramData['referral-link'] = 10;
                $loyaltyProgramData['referral-first-order'] = 300;
                $loyaltyProgramData['review'] = 10;
                break;
            case (2):
                $loyaltyProgramData['sign-up'] = 100;
                $loyaltyProgramData['review'] = 10;
                $loyaltyProgramData['newsletter'] = 50;
                $loyaltyProgramData['birthday'] = 200;
                $loyaltyProgramData['inactivity-points'] = 100;
                $loyaltyProgramData['inactivity-period'] = 180;
                $loyaltyProgramData['anniversary'] = 200;
                break;
            case (3):
                $loyaltyProgramData['sign-up'] = 100;
                $loyaltyProgramData['review'] = 10;
                $loyaltyProgramData['newsletter'] = 50;
                $loyaltyProgramData['birthday'] = 200;
                $loyaltyProgramData['inactivity-points'] = 100;
                $loyaltyProgramData['inactivity-period'] = 180;
                $loyaltyProgramData['anniversary'] = 200;
                $loyaltyProgramData['social-actions'] = 10;
                $loyaltyProgramData['referral-link'] = 10;
                $loyaltyProgramData['referral-first-order'] = 300;
                break;
        }
        
        return $loyaltyProgramData;
    }
    
    public function getAllData()
    {
        return array_merge($this->getProgramData(), $this->getCustomizedLoyaltyProgramData());
    }
}

