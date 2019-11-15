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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Manage Special Edit Tab Conditions
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Special_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry ( 'global_manage_special_rule' );

        $form = new Varien_Data_Form ();
        $form->setHtmlIdPrefix ( 'rule_' );

        $fieldset = $form->addFieldset ( 'trigger_fieldset', array ('legend' => Mage::helper ( 'salesrule' )->__ ( 'Triggers' ) ) );
        $ruleElem = $points_conditions_field = $fieldset->addField ( 'points_conditions', 'select', array (
            'label' => Mage::helper ( 'salesrule' )->__ ( 'Customer Action or Event' ),
            'name' => 'points_conditions',
            'options' => Mage::getSingleton ( 'rewards/special_action' )->getOptionsArray (),
            'required' => true,
            'onchange'=>'showNote(this);',
        ));

        $configLink = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/rewards");
        $msgSelectReviewOrTag = Mage::helper("rewards")->__(
            "Now you can set daily and weekly limits. You can %s Change these settings %s", 
            "<i><a href=\"{$configLink}\" target=\"_blank\">", 
            "</a>.</i>"
        );
        
        $fieldset->addField('rewards_and_tags_note', 'note', array(
            'text' => $msgSelectReviewOrTag
        ));
        
        $dependence = '';
        if (Mage::helper('rewards/version')->isBaseMageVersionAtLeast('1.7.0.1')) {
            $dependence = array(
                TBT_Rewards_Model_Special_Action::ACTION_WRITE_REVIEW, 
                TBT_Rewards_Model_Special_Action::ACTION_TAG
            );
        } else {
            $dependence = TBT_Rewards_Model_Special_Action::ACTION_WRITE_REVIEW;
        }
        
        $this->setChild('form_after', $this->getLayout()
            ->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap('rule_points_conditions', 'rule_points_conditions')
            ->addFieldMap('rule_rewards_and_tags_note', 'rule_rewards_and_tags_note')
            ->addFieldDependence('rule_rewards_and_tags_note', 'rule_points_conditions', $dependence) 
        );

        $msgSelectFirstOrder = Mage::helper("rewards")->__('This rule will not apply for GUEST users because of security implications.');        
        $ruleElem->setAfterElementHtml(
          "<script>
              function showNote(elem) {
                 if($('note_points_conditions') != null) {
                    $('note_points_conditions').remove();
                 }
                 if(elem.value == '" . TBT_RewardsReferral_Model_Special_Firstorder::ACTION_REFERRAL_FIRST_ORDER ."') {
                    $('rule_points_conditions').insert({after:'<p id=\'note_points_conditions\' class=\'note\' style=\'color:red;\'>" .$msgSelectFirstOrder."</p>'});
                 }
              }
           </script>"
        );

        Mage::getSingleton('rewards/wikihints')->addWikiHint($fieldset, "article/402-customer-behavior-rule-triggers", "Customer Behavior Rule - Triggers" );

        Mage::getSingleton ( 'rewards/special_action' )->visitAdminTriggers ( $fieldset );
        
        
        $fieldset = $form->addFieldset ( 'conditions_fieldset', array ('legend' => Mage::helper ( 'salesrule' )->__ ( 'Conditions' ) ) );
        Mage::getSingleton('rewards/wikihints')->addWikiHint($fieldset, "article/409-customer-behavior-rule-conditions", "Customer Behavior Rule - Conditions" );
        $customerGroups = Mage::getResourceModel ( 'customer/group_collection' )->load ()->toOptionArray ();

        foreach ( $customerGroups as $group ) {
            if ($group ['value'] == 0) {
                //Removes the "Not Logged In" option, becasue its redundant for special rules
                array_shift ( $customerGroups );
            }
        }

        $fieldset->addField ( 'customer_group_ids', 'multiselect', array ('name' => 'customer_group_ids[]', 'label' => Mage::helper ( 'salesrule' )->__ ( 'Customer Group Is' ), 'title' => Mage::helper ( 'salesrule' )->__ ( 'Customer Group Is' ), 'required' => true, 'values' => $customerGroups ) );

        $dateFormatIso = Mage::app ()->getLocale ()->getDateFormat ( Mage_Core_Model_Locale::FORMAT_TYPE_SHORT );
        $fieldset->addField ( 'from_date', 'date', array ('name' => 'from_date', 'label' => Mage::helper ( 'salesrule' )->__ ( 'Date is on or After' ), 'title' => Mage::helper ( 'salesrule' )->__ ( 'Date is on or After' ), 'image' => $this->getSkinUrl ( 'images/grid-cal.gif' ), 'input_format' => Varien_Date::DATE_INTERNAL_FORMAT, 'format' => $dateFormatIso ) );
        $fieldset->addField ( 'to_date', 'date', array ('name' => 'to_date', 'label' => Mage::helper ( 'salesrule' )->__ ( 'Date is Before' ), 'title' => Mage::helper ( 'salesrule' )->__ ( 'Date is Before' ), 'image' => $this->getSkinUrl ( 'images/grid-cal.gif' ), 'input_format' => Varien_Date::DATE_INTERNAL_FORMAT, 'format' => $dateFormatIso ) );

        Mage::getSingleton ( 'rewards/special_action' )->visitAdminConditions ( $fieldset );

        $form->setValues ( $model->getData () );
        $this->setForm ( $form );

        return parent::_prepareForm ();
    }
}
