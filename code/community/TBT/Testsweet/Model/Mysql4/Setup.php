<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

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
 * @package    [TBT_Testsweet]
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Rewards Setup Model
 *
 * @category   TBT
 * @package    TBT_Testsweet
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Testsweet_Model_Mysql4_Setup extends TBT_Rewards_Model_Mysql4_Setup
{
    /**
     * Runs after additional data update scripts have been executed
     * @return TBT_Rewards_Model_Mysql4_Setup
     */
    protected function _postApplyData()
    {
        parent::_postApplyData();
        $conflicts = Mage::getModel('testsweet/test_suite_magento_module_conflict')->getSummary();
        $database = Mage::getModel('testsweet/test_suite_rewards_database_tables')->getSummary();

        foreach ($conflicts as $testcase) {
            if ($testcase->getStatus() === TBT_Testsweet_Model_Test_Report_Abstract::STATUS_FAIL) {
                Mage::getConfig()->saveConfig('rewards/notifications/extension_conflicts_found', 1);
                Mage::app()->getCacheInstance()->cleanType('config');
                break;
            }
        }

        foreach ($database as $testcase) {
            if ($testcase->getStatus() === TBT_Testsweet_Model_Test_Report_Abstract::STATUS_FAIL) {
                Mage::getConfig()->saveConfig('rewards/notifications/database_structure_incoherent', 1);
                Mage::app()->getCacheInstance()->cleanType('config');
                break;
            }
        }

        return $this;
    }
}

