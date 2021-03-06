<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the BETTER STORE SEARCH
 * License, which is available at this URL: http://www.betterstoresearch.com/docs/bss_license.txt
 * 
 * DISCLAIMER
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
 * @package    [TBT_Bss]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 * @license    http://www.betterstoresearch.com/docs/bss_license.txt
*/

/**
 *
 * @category   TBT
 * @package    TBT_Bss
 * @author     Sweet Tooth Better Store Search Team <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Debug_Profiler extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
    	$profiler_id = 'sweet_tooth_profiler_section';
        #$out = '<div style="position:fixed;bottom:5px;right:5px;opacity:.1;background:white" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.1">';
        #$out = '<div style="opacity:.1" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.1">';
        $out = "<a href=\"javascript:void(0)\" onclick=\"$('bss_search_profiler_section').style.display=$('{$profiler_id}').style.display==''?'none':''\">[MageRewards Profiler]</a>";
        $out .= "<div id='{$profiler_id}' style='background:white; display:block;' align='left'>";
        //$out .= '<pre>Memory usage: real: '.memory_get_usage(true).', emalloc: '.memory_get_usage().'</pre>';

        $tracker_result =  nl2br(Mage::helper('rewards/debug_tracker')->getTrackedEventsAsString(false) );
        
        $out .= $tracker_result;
        
        $out .= '</div>';

        //@nelkaake -a 17/02/11: If the ranking table is empty, what's the point of displaying the rest of this?
        if(empty($tracker_result)) return "";
        
        return $out;
    }
    
}
