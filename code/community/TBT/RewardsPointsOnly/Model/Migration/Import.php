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
 * Add export utilities to points only rules
 * @package     TBT_RewardsPointsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Model_Migration_Import extends TBT_Rewards_Model_Migration_Import
{
    const DATA_POINTS_ONLY_RULE = TBT_RewardsPointsOnly_Model_Migration_Export::DATA_POINTS_ONLY_RULE;

    /**
     * Imports from an array of data Sweet Tooth rules, configuration settings & currency data. 
     * Whatever is present.
     *
     * @param  array $data | Array of data to be imported.
     * @override TBT_Rewards_Model_Migration_Import::_importFromData()
     * @return $this
     */
    protected function _importFromData($data)
    {
        parent::_importFromData($data);
        
        if (isset($data[self::DATA_POINTS_ONLY_RULE]) && !empty($data[self::DATA_POINTS_ONLY_RULE])) {
            $this->_importPointsOnlyRules($data[self::DATA_POINTS_ONLY_RULE]);
        }

        return $this;
    }

    /**
     * Import points only rules
     *
     * @param  array $rules | Collection of rules
     * @return this
     */
    protected function _importPointsOnlyRules($rules)
    {
        return $this->_importModelData($rules, 'rewardspointsonly/rule');
    }
}

