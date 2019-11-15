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
 * @package    [TBT_Common]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Menu
 *
 * @category   TBT
 * @package    TBT_Common
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Common_Block_Adminhtml_Page_Menu extends Mage_Adminhtml_Block_Page_Menu {

    public function __construct() {
        parent::__construct ();
    }

    /**
     * Get menu level HTML code
     *
     * @param array $menu
     * @param int $level
     * @return string
     */
    public function getMenuLevel($menu, $level = 0)
    {
        // here we make sure that Sweet Tooth icon is displayed in Admin top menu in Magento 1.7.0.0
        // ST-1368
        $html = '<ul ' . (!$level ? 'id="nav"' : '') . '>' . PHP_EOL;
        foreach ($menu as $index => $item) {

                $html .= '<li ' . (!empty($item['children']) ? 'onmouseover="Element.addClassName(this,\'over\')" '
                    . 'onmouseout="Element.removeClassName(this,\'over\')"' : '') . ' class="'
                    . (!$level && !empty($item['active']) ? ' active' : '') . ' '
                    . (!empty($item['children']) ? ' parent' : '')
                    . (!empty($level) && !empty($item['last']) ? ' last' : '')
                    . ' level' . $level . '"> <a href="' . $item['url'] . '" '
                    . (!empty($item['title']) ? 'title="' . $item['title'] . '"' : '') . ' '
                    . (!empty($item['click']) ? 'onclick="' . $item['click'] . '"' : '') . ' class="'
                    . ($level === 0 && !empty($item['active']) ? 'active' : '') . '">'

                    // only for Sweet Tooth menu we'll change html code returned
                    . ($index == 'rewards' ? $item['label'] : '<span>' . $this->escapeHtml($item['label']) . '</span>')
                    . '</a>' . PHP_EOL;

                if (!empty($item['children'])) {
                    $html .= $this->getMenuLevel($item['children'], $level + 1);
                }
                $html .= '</li>' . PHP_EOL;
        }
        
        $html .= '</ul>' . PHP_EOL;

        return $html;

    }
}