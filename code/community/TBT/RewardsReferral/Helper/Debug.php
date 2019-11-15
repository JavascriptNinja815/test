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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper for Debugging
 *
 * @category   TBT
 * @package    TBT_RewardsReferral
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Helper_Debug extends Mage_Core_Helper_Abstract 
{
    public function dd($vo, $with_pre_tags = true) 
    {
        $str = ($vo instanceof Varien_Object)
            ? print_r($vo->toArray(), true)
            : print_r($vo, true);

        if ($with_pre_tags) {
            $str = "<PRE>" . $str . "</PRE>";
        }
        
        Mage::helper('rewards/debug')->printMessage($str);
    }

    /**
     * @return  a simple backtrace string of the current position (not including
     *          any travering into this function)
     * @nelkaake Added on Monday August 20, 2010:      
     */
    public function getSimpleBacktrace($offset=1) {
        $str = "";
        $bt = debug_backtrace();
        foreach ($bt as $index => &$btl) {
            if ($offset > 0) {
                $offset--;
                continue;
            }
            $str .= "> {$btl['file']}";
            $str .= " in function {$btl['function']}(*)";
            $str .= " on line {$btl['line']}.";
            $str .= "\n";
        }
        return $str;
    }

    /**
     * @return  a simple backtrace string of the current position (not including
     *          any travering into this function)  
     * @nelkaake Added on Monday August 20, 2010:   
     */
    public function noticeBacktrace($msg="") {
        Mage::helper('rewardsref')->notice($msg . "\n" . $this->getSimpleBacktrace(2));
        return $this;
    }

}
