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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Quick Launch Helper
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */

class TBT_Rewards_Helper_QuickLaunch extends Mage_Core_Helper_Abstract
{
    protected $firstStep = 'connect';
    protected $lastStep = 'review';
    
    protected $steps = array(
        'connect' => array(
            'title' => 'Connect to MageRewards',
            'template' => 'rewards/quickLaunch/steps/connect.phtml',
            'action' => 'rewards/quickLaunch::connectAccount',
            'condition' => 'rewards/quickLaunch::isAccountConnected',
            'next-step' => 'settings'
        ),
        'settings' => array(
            'title' => 'Loyalty Program Goals & Settings',
            'template' => 'rewards/quickLaunch/steps/settings.phtml',
            'action' => 'rewards/quickLaunch::saveSettings',
            'condition' => 'rewards/quickLaunch::hasLoyaltyProgram',
            'next-step' => 'review'
        ),
        'review' => array(
            'title' => 'Review & Launch',
            'template' => 'rewards/quickLaunch/steps/review.phtml',
            'action' => 'rewards/quickLaunch::launchProgram',
        ),
    );
    
    public function getFirstStep()
    {
        return $this->firstStep;
    }
    
    public function isLastStep($step)
    {
        return ($step === $this->lastStep);
    }
    
    public function getNextStep($step)
    {
        if (!isset($this->steps[$step])) {
            $step = $this->getFirstStep();
        }
        
        if (!$this->isLastStep($step) && isset($this->steps[$step]['condition'])) {
            list($class, $method) = explode('::', $this->steps[$step]['condition']);
            
            if (Mage::getModel($class)->$method()) {
                return $this->getNextStep($this->steps[$step]['next-step']);
            }
        }
        
        return $step;
    }
    
    public function getData($step)
    {
        if (!isset($this->steps[$step])) {
            return $this->steps[self::getFirstStep()];
        }
        
        return $this->steps[$step];
    }
    
    public function executeAction($data)
    {
        $step = (isset($data['step'])) ? $data['step'] : null;
        
        if (!(isset($this->steps[$step]['action']))) {
            return false;
        }
        
        list($class, $method) = explode('::', $this->steps[$step]['action']);
        return Mage::getModel($class)->$method($data);
    }
}