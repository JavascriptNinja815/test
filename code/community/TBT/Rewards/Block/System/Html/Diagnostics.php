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
 * @package    TBT_Rewards
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */ 

/**
 * Diagnostics Html Frontend Model
 *
 * @see TBT_Rewards_Model_Newsetter_Subscription_Observer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_System_Html_Diagnostics extends TBT_Rewards_Block_System_Html
{
    /**
     * Renderer
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
		$html = "";
		$st_plat_ver = Mage::getConfig()->getNode('modules/TBT_Rewards/version');
		
		$html .= "
        	<div style=\" margin-bottom: 12px; width: 100%;\">
		";
		
		//  (core v{$st_core_ver}, api v{$st_api_ver}, points-only v{$st_only_ver})
        $html .= "
            <p>You are currently running MageRewards Version {$st_plat_ver}.</p>
            
            <p>Check out the <a href='http://www.magerewards.com/resources/' target='st_docs'>MageRewards documentation</a> or 
            <a href='http://support.magerewards.com/' target='st_support'>contact the support team</a> if you need help.</p>
        ";
        
		$html .= "
            </div>
		";

        return $html;
    }

}
