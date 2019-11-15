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
 * Manage Promo Rewards PointsOnly Edit Tab Actions
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Manage_Promo_PointsOnly_Edit_Tab_Actions
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Get Tab Label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('rewardspointsonly')->__('Actions');
    }

    /**
     * Get Tab Title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('rewardspointsonly' )->__('Actions');
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = $this->_getRule();

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix ('rule_');

        Mage::dispatchEvent(
            'rewards_block_manage_actions_setup_pointsonly_rule_before',
            array(
                'actions_block' => $this,
                'rule' => $model,
                'form' => $form
            )
        );

        $fieldset = $form->addFieldset(
            'points_action_fieldset',
            array (
                'legend' => Mage::helper('rewardspointsonly')->__('Customer Spends Points')
            )
        );
        Mage::getSingleton('rewards/wikihints')->addWikiHint(
            $fieldset,
            "article/386-spending-amounts",
            "PointsOnly Spending Rule - Conditions - Spending Amounts"
        );

        $options = $model->getRedemptionOptionArray();

        $fieldset->addField(
            'points_action',
            'select',
            array(
                'label' => Mage::helper('rewardspointsonly')->__('Customer Spending Style'),
                'name' => 'points_action',
                'options' => $options,
                'onchange' => 'toggleActionsSelect(this.value)'
            )
        );

        $fieldset->addField(
            'points_amount',
            'text',
            array(
                'name' => 'points_amount',
                'required' => true,
                'class' => 'validate-not-negative-number',
                'label' => Mage::helper('rewardspointsonly')->__('Points Amount (X)')
            )
        );

        $fieldset->addField(
            'points_amount_step',
            'text',
            array(
                'name' => 'points_amount_step',
                'label' => Mage::helper('rewardspointsonly')->__('Monetary Step (Y)')
            )
        );

        $model_data = $model->getData ();

        Mage::dispatchEvent( 'rewards_block_manage_actions_setup_pointsonly_rule_before', array(
            'actions_block' => $this,
            'rule'	        => $model,
            'form'			=> $form
        ) );

        $form->setValues ( $model_data );
        $this->setForm ( $form );

        return $this;
    }

    /**
     * Fetches the currently opened Points Only Rule.
     *
     * @return TBT_Rewards_Model_Catalogrule_Rule
     */
    protected function _getRule()
    {
        return Mage::registry('current_promo_pointsonly_rule');
    }
}
