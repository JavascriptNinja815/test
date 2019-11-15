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
 * Manage Promo Rewards PointsOnly Edit Tab Main
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Manage_Promo_PointsOnly_Edit_Tab_Main
    extends TBT_Rewards_Block_Admin_Widget_Form_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
	 * Prepare label for tab
	 *
	 * @return string
	 */
	public function getTabLabel()
    {
		return Mage::helper('rewardspointsonly')->__('Rule Info');
	}

	/**
	 * Prepare title for tab
	 *
	 * @return string
	 */
	public function getTabTitle()
    {
		return Mage::helper('rewardspointsonly')->__('Rule Info');
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

    /**
     * Prepare Form
     * @return Mage_Adminhtml_Block_Widget_Form
     */
	protected function _prepareForm()
    {
		$model = $this->_getRule();
		
		$form = new Varien_Data_Form();
		
		$form->setHtmlIdPrefix('rule_');
		
		$fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => Mage::helper('rewardspointsonly')->__('General Information')
            )
        );
		
		$fieldset->addField('auto_apply', 'hidden', array('name' => 'auto_apply'));
		
		if ($model->getId()) {
			$fieldset->addField('rule_id', 'hidden', array('name' => 'rule_id'));
		}
		
		$fieldset->addField(
            'name',
            'text',
            array(
                'name' => 'name',
                'label' => Mage::helper('rewardspointsonly')->__('Rule Name'),
                'title' => Mage::helper('rewardspointsonly')->__('Rule Name'),
                'required' => true
            )
        );
		
		$fieldset->addField(
            'description',
            'textarea',
            array(
                'name' => 'description',
                'label' => Mage::helper('rewardspointsonly')->__('Description'),
                'title' => Mage::helper('rewardspointsonly')->__('Description'),
                'style' => 'height: 100px;'
            )
        );
		
        $fieldset->addField(
            'is_active',
            'select',
            array(
                'label' => Mage::helper('rewardspointsonly')->__('Status'),
                'title' => Mage::helper('rewardspointsonly')->__('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => array(
                    '1' => Mage::helper('rewardspointsonly')->__('Active'),
                    '0' => Mage::helper('rewardspointsonly')->__('Inactive')
                )
            )
        );
		
		if (!Mage::app()->isSingleStoreMode()) {
			$fieldset->addField(
                'website_ids',
                'multiselect',
                array(
                    'name' => 'website_ids[]',
                    'label' => Mage::helper('rewardspointsonly')->__('Websites'),
                    'title' => Mage::helper('rewardspointsonly')->__('Websites'),
                    'required' => true,
                    'values' => Mage::getSingleton('adminhtml/system_config_source_website')->toOptionArray()
                )
            );
		} else {
			$fieldset->addField(
                'website_ids',
                'hidden',
                array(
                    'name' => 'website_ids[]',
                    'value' => Mage::app()->getStore(true)->getWebsiteId()
                )
            );

			$model->setWebsiteIds(Mage::app()->getStore(true)->getWebsiteId());
		}
		
		$customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
		
		$found = false;

		foreach ($customerGroups as $group) {
			if ($group['value'] == 0) {
				$found = true;
			}
		}
		if (!$found) {
			array_unshift(
                $customerGroups,
                array(
                    'value' => 0,
                    'label' => Mage::helper('rewardspointsonly')->__('NOT LOGGED IN')
                )
            );
		}
		
		$fieldset->addField(
            'customer_group_ids',
            'multiselect',
            array(
                'name' => 'customer_group_ids[]',
                'label' => Mage::helper('rewardspointsonly')->__('Customer Groups'),
                'title' => Mage::helper('rewardspointsonly')->__('Customer Groups'),
                'required' => true,
                'values' => $customerGroups
            )
        );
		
		$dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
		$fieldset->addField(
            'from_date',
            'date',
            array(
                'name' => 'from_date',
                'label' => Mage::helper('rewardspointsonly')->__('From Date'),
                'title' => Mage::helper('rewardspointsonly' )->__('From Date'),
                'image' => $this->getSkinUrl('images/grid-cal.gif'),
                'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
                'format' => $dateFormatIso
            )
        );

		$fieldset->addField(
            'to_date',
            'date',
            array(
                'name' => 'to_date',
                'label' => Mage::helper('rewardspointsonly')->__('To Date'),
                'title' => Mage::helper('rewardspointsonly')->__('To Date'),
                'image' => $this->getSkinUrl('images/grid-cal.gif'),
                'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
                'format' => $dateFormatIso
            )
        );
		
		$element = $fieldset->addField(
            'sort_order',
            'text',
            array(
                'name' => 'sort_order',
                'label' => Mage::helper('rewardspointsonly')->__('Priority')
            )
        );
		Mage::getSingleton('rewards/wikihints')->addWikiHint(
            $element, "article/408-rule-priority", "Rule Priority"
        );
		
		$form->setValues($model->getData());
		
		$this->setForm ($form);
		
		return parent::_prepareForm();
	}

    /**
     * Fetches the currently open TBT_Rewards_Model_Catalogrule_Rule.
     *
     * @return TBT_Rewards_Model_Catalogrule_Rule
     */
    protected function _getRule()
    {
        return Mage::registry('current_promo_pointsonly_rule');
    }
}
