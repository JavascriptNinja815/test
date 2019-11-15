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
 * Widget Social Block
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Block_Widget_Social extends TBT_RewardsSocial2_Block_Social
    implements Mage_Widget_Block_Interface
{
    /**
     * Get available social buttons
     * @return array
     */
    public function getAvailableSocialButtons()
    {
        if (is_null($this->availableButtons)) {
            $buttons = $this->getSocialButtonsSelector();
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
        
        if ($button === 'PINTEREST_PIN') {
            return false;
        }
        
        $availableButtons = $this->getAvailableSocialButtons();
        
        return in_array(constant('TBT_RewardsSocial2_Model_System_Config_Source_Homepage::' . $button), $availableButtons);
    }
}