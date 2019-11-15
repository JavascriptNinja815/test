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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * FPC PLaceholder for social referal modal window
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Model_PageCache_Container_ShareModal extends Enterprise_PageCache_Model_Container_Abstract
{
    /**
     * Cache tag prefix
     */
    const CACHE_TAG_PREFIX = 'REWARDSSOCIAL2_SHARE_MODAL';

    /**
     * Get container individual cache id
     *
     * @return string
     */
    public function getCacheId()
    {
        return $this->_getCacheId();
    }

    /**
     * Apply Content in App
     * @param string $content
     * @return boolean
     */
    public function applyInApp(&$content)
    {
        $block = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');

        $block = new $block;
        $block->setTemplate($template);
        $block->setLayout(Mage::app()->getLayout());    

        $blockContent = $block->toHtml();

        $this->_applyToContent($content, $blockContent);

        return true;
    }
    
    /**
     * Save rendered block content to cache storage
     *
     * @param string $blockContent
     * @return $this
     */
    public function saveCache($blockContent, $tags = array())
    {
        return false;
    }
    
    /**
     * Get container individual cache id
     *
     * @return string
     */
    protected function _getCacheId()
    {
        $cacheId = self::CACHE_TAG_PREFIX . md5($this->_getIdentifier());
        return $cacheId;
    }

    /**
     * Return unique Customer cookie
     * @return string
     */
    protected function _getIdentifier()
    {
        return $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER, '');
    }

}