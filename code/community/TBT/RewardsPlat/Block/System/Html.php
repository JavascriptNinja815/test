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
 * @category   Sweet Tooth
 * @package    TBT_Enhancedgrid
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */ 


class TBT_RewardsPlat_Block_System_Html extends TBT_Rewards_Block_System_Html {
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
		$html = "";
		//$st_core_ver = Mage::getConfig()->getNode('modules/TBT_Rewards/version');
		$st_plat_ver = Mage::getConfig()->getNode('modules/TBT_RewardsPlat/version');
		//$st_api_ver = Mage::getConfig()->getNode('modules/TBT_RewardsApi/version');
		//$st_only_ver = Mage::getConfig()->getNode('modules/TBT_RewardsOnly/version');
		
		$html .= "
        	<div style=\" margin-bottom: 12px; width: 100%;\">
		";
		
		//  (core v{$st_core_ver}, api v{$st_api_ver}, points-only v{$st_only_ver})
        $html .= "
            <p>You are currently running Sweet Tooth Platinum Version {$st_plat_ver}.</p>
            
            <p>Check out the <a href='https://www.sweettoothrewards.com/wiki' target='st_docs'>Sweet Tooth documentation</a> or 
            <a href='https://www.wdca.ca/support' target='st_support'>contact the support team</a> if you need help.</p>
            
            <p>For updates, please subscribe to the <a href='https://www.getsweettooth.com/news' target='_blank'>Sweet Tooth news blog</a>.</p>
        ";
        
		$html .= Mage::getBlockSingleton('rewards/manage_widget_loyalty')->toHtml();
		
		$html .= "
            </div>
		";

        return $html;
    }

}
