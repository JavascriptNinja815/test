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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Manage Promo Rewards PointsOnly Form Container
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Manage_Promo_PointsOnly_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Main Constructor
     */
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'manage_promo_pointsOnly';
        $this->_blockGroup = 'rewardspointsonly';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('rewardspointsonly')->__('Save Rule'));
        $this->_updateButton('delete', 'label', Mage::helper('rewardspointsonly')->__('Delete Rule'));

        $deduct_points_action = 'deduct_points';
        $deduct_by_amount_spent_action = 'deduct_by_amount_spent';

        $this->_formScripts [] = "
        Validation.add('validate-notzero', '" . $this->__('This value cannot be less than or equal to zero.') . "', function (v) {
             return parseFloat(v) > 0;
        });
        ";

        $this->_formInitScripts [] = "
            function toggleActionsSelect(action) {
                var rule_points_amount_step_row = $('rule_points_amount_step').up().up();
                if ($('rule_points_amount_step_container') != undefined){
                    rule_points_amount_step_row = $('rule_points_amount_step_container').up().up();
                }

                if(action == '$deduct_points_action') {
                    rule_points_amount_step_row.hide();
                    $('rule_points_amount_step').removeClassName('required').removeClassName('validate-notzero');
                } else if(action == '$deduct_by_amount_spent_action') {
                    rule_points_amount_step_row.show();
                    $('rule_points_amount_step').addClassName('required').addClassName('validate-notzero');
                } else {
                    rule_points_amount_step_row.show();
                    $('rule_points_amount_step').addClassName('required').addClassName('validate-notzero');
                }
            }
        ";
        $this->_formInitScripts [] = "if ($('rule_points_action')) { toggleActionsSelect($('rule_points_action').value); }";
    }

    /**
     * Get Header Text
     * @return string
     */
    public function getHeaderText()
    {
        $rule = $this->_getRule();
        
        if ($rule && $rule->getRuleId()) {
            return Mage::helper('rewardspointsonly')->__("Edit Rule '%s'", $this->htmlEscape($rule->getName()));
        } else {
            return Mage::helper('rewardspointsonly')->__('New Rule');
        }
    }

    /**
     * Fetches the currently open catalogrule.
     *
     * @return TBT_Rewards_Model_Catalogrule_Rule
     */
    protected function _getRule()
    {
        return Mage::registry('current_promo_pointsonly_rule');
    }

    /**
     * Get Back Url
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * Get Save Url
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save');
    }
}
