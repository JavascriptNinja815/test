<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TBT_RewardsSocial2_Block_System_Config_Html extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '</tbody></table>'
            . '<div style="padding: 5px;">';


        $html .= '<p style="margin: 20px 0 30px 0;">You can integrate MageRewards social buttons into individual <strong>CMS pages</strong> as well. <br/> '
            . '<a href="http://support.magerewards.com/article/1627-rewards-social-2-0-admin-functionality#cms" target="_blank" title="Learn more">Learn More</a>'
        . '</p>'
        . '<hr />';

        
        $html .= '<h6 style="margin: 20px 0;"><strong>Security Limits</strong></h6>'
                . '<p>Specify below, the number of times each customer can earn points from all social engagements.'
                    . '<br />Usage will reset at the start of each calendar period.'
                . '</p>'
            . '</div>'
        . '<table><tbody>';
        
        return $html;
    }
}
