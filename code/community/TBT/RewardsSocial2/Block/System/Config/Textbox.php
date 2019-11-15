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
class TBT_RewardsSocial2_Block_System_Config_Textbox extends Varien_Data_Form_Element_Abstract
{   
    public function getHtml()
    {
        $this->addClass('input-text');
        $html = parent::getHtml();
        $value = $this->getValue();
        
        $checked = '';
        if ($value) {
            $checked = 'checked';
        } else {
            $pos = strpos($html, 'class=" input-text"');
            $html = substr_replace($html, ' readonly ', $pos, 0);
        }
        
        $htmlId = $this->getHtmlId();
        $checkbox = '<td class="check-field"><input type="checkbox" onclick="'
            . 'if (this.checked) {'
                . '$(' . $htmlId . ').setValue(1).removeAttribute(\'readonly\');'
            . '} else {'
                . '$(' . $htmlId . ').clear().setAttribute(\'readonly\', true); '
            . '}'
        . '" ' . $checked . '/></td>';
        
        $pos = strpos($html, '<td class="label">');
        $html = substr_replace($html, $checkbox, $pos, 0);
        
        return $html;
    }
}
