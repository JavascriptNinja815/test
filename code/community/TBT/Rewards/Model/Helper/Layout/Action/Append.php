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
 * @package    [TBT_Rewards]
 * @subpackage [Model]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Handler for Layout Action
 *
 * @package    TBT_Rewards
 * @subpackage Model
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Helper_Layout_Action_Append
{
    /**
     * Parent Block Instance
     * @var mixed|Mage_Core_Block_Abstract
     */
    private $_parentBlock;
    
    /**
     * Before Html that will be appended to parent block
     * @var string 
     */
    private $_beforeBlockHtml;
    
    /**
     * After Html that will be appended to parent block
     * @var string 
     */
    private $_afterBlockHtml;
    
    /**
     * Config path with boolean value to append or not html to parent block
     * @var string 
     */
    private $_ifConfig;
    
    /**
     * Main Constructor
     * @param Mage_Core_Block_Abstract $parentBlock
     * @param string $ifConfig
     */
    public function __construct($parentBlock = null, $ifConfig = null)
    {
        $this->_parentBlock = $parentBlock;
        
        $this->_ifConfig = $ifConfig;
    }
    
    /**
     * Setter for parent block
     * @param Mage_Core_Block_Abstract $parentBlock
     * @return \TBT_Rewards_Model_Helper_Layout_Action_Append
     */
    public function setParentBlock($parentBlock)
    {
        $this->_parentBlock = $parentBlock;
        
        return $this;
    }
    
    /**
     * Setter for config path used to decide if the html will be appended 
     * to parent block
     * @param string $ifConfig
     * @return \TBT_Rewards_Model_Helper_Layout_Action_Append
     */
    public function setIfConfig($ifConfig)
    {
        $this->_ifConfig = $ifConfig;
        
        return $this;
    }
    
    /**
     * Add block content or instance to be added as html before or after parent block
     * @param string|Mage_Core_Block_Abstract $block
     * @param string $position {'before', 'after'}
     * @return \TBT_Rewards_Model_Helper_Layout_Action_Append
     */
    public function add($block, $position = 'before')
    {
        if ($block instanceof Mage_Core_Block_Abstract) {
            $block = $block->toHtml();
        }
        
        if ($position === 'after') {
            $this->_afterBlockHtml .= $block;
        } else {
            $this->_beforeBlockHtml .= $block;
        }
        
        return $this;
    }
    
    /**
     * Appends the html from all defined block to parent block and after clears
     * this instance properties
     * @return \TBT_Rewards_Model_Helper_Layout_Action_Append
     */
    public function append()
    {
        if (!($this->_parentBlock instanceof Mage_Core_Block_Abstract)) {
            return $this->_clearData();
        }
        
        if (!is_null($this->_ifConfig) && !Mage::getStoreConfigFlag($this->_ifConfig)) {
            return $this->_clearData();
        }
        
        if (is_null($this->_afterBlockHtml) && is_null($this->_beforeBlockHtml)) {
            return $this->_clearData();
        }
        
        $openTag = '!-- --';
        $closeTag = '!-- --';
        
        if (!is_null($this->_beforeBlockHtml)) {
            $openTag = '!-- BEGIN REWARDS INTEGRATION -->'
                . $this->_beforeBlockHtml
                . '<!-- END REWARDS INTEGRATION --';
        }
        
        if (!is_null($this->_afterBlockHtml)) {
            $closeTag = '!-- BEGIN REWARDS INTEGRATION -->'
                . $this->_afterBlockHtml
                . '<!-- END REWARDS INTEGRATION --';
        }
        
        $this->_parentBlock->setFrameTags($openTag, $closeTag);
        
        return $this->_clearData();
    }
    
    /**
     * Clears properties of this instance to make sure that we do not override
     * anything when this class has multiple usages
     * @return \TBT_Rewards_Model_Helper_Layout_Action_Append
     */
    private function _clearData()
    {
        $this->_parentBlock = null;
        $this->_ifConfig = null;
        $this->_beforeBlockHtml = null;
        $this->_afterBlockHtml = null;
        
        return $this;
    }
}