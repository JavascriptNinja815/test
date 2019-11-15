<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
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
 */

/**
 * Tracking Block
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Tracking extends Mage_Core_Block_Template 
{
    protected $token;
    
    protected function _construct() 
    {
        $this->setTemplate('rewards/tracking.phtml');
    }
    
    public function getToken()
    {
        if (!$this->token) {
            $this->token = Mage::helper('rewards/tracking')->getToken();
        }
        
        return $this->token;
    }
    
    public function getIdentity()
    {
        return Mage::helper('rewards/tracking')->getIdentity();
    }
    
    public function getGlobalData()
    {
        return Mage::helper('rewards/tracking')->getGlobalData();
    }
    
    public function isRenderedTheFirstTime()
    {
        if (!Mage::registry('tracking_code_is_rendered')) {
            Mage::register('tracking_code_is_rendered', true);
            return true;
        }
        
        return false;
    }
    
    public function isTrackingEnabled()
    {
        $token = $this->getToken();
        return (!empty($token));
    }
}

