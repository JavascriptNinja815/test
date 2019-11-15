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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Facade Service part used to remove Points Only
 * @package     TBT_RewardsPointsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Model_Service_ProcessRules_Remove extends Varien_Object
{
    /**
     * Remove Points Only from Cart
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function removePoints(Mage_Sales_Model_Quote $quote)
    {
        foreach ($quote->getAllItems() as $item) {
            $this->_writePointsData($item);
        }
    }

    /**
     * Write Points Only Data
     * @param mixed $item
     * @param array $ruleData
     * @return \TBT_RewardsPointsOnly_Model_Service_ProcessRules_Remove
     */
    protected function _writePointsData($item, $ruleData = array())
    {
        $ruleHash = Mage::helper('rewards')->hashIt($ruleData);
        $item->setRewardsPointsonlyHash($ruleHash);

        return $this;
    }

    /**
     * Get Points Only Rules
     *
     * @note Rules should be loaded with singleton model to preserve performance
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    protected function _getRules($websiteId, $customerGroupId)
    {
        return Mage::getSingleton('rewardspointsonly/service_processRules_rules')
            ->getRules($websiteId, $customerGroupId);
    }
}
