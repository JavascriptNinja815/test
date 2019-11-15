<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
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
 */

/**
 * Milestone Manage History Transfers Tab
 *
 * @category   TBT
 * @package    TBT_Milestone
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Milestone_Block_Manage_History_View_Tab_Transfers extends Mage_Adminhtml_Block_Widget_Grid implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $_collection = null;
    protected $_columnsSet = false;

    protected function _construct()
    {
        parent::_construct();

        $this->setId('milestoneRuleLogTransfers');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);

        return $this;
    }

    protected function _prepareCollection()
    {
        if (is_null($this->_collection)) {
            $ruleLog          = $this->_getCurrentLog();
            $this->_collection = Mage::getModel('rewards/transfer')->getCollection()
                ->addFieldToFilter('reference_id', $ruleLog->getId())
                ->addFieldToFilter('customer_id', $ruleLog->getCustomerId())
                ->selectPointsCaption('points');
        }

        $this->setCollection($this->_collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if ($this->_columnsSet) {
            return parent::_prepareColumns();
        }

        $this->_columnsSet = true;

        $this->addColumn('transfer_id', array(
            'header'       => Mage::helper('tbtmilestone')->__('ID'),
            'align'        => 'right',
            'width'        => '36px',
            'index'        => 'rewards_transfer_id',
            'filter_index' => 'main_table.rewards_transfer_id',
        ));

        $this->addColumn('points', array(
            'header'       => Mage::helper('tbtmilestone')->__('Points'),
            'align'        => 'left',
            'width'        => '70px',
            'index'        => 'points',
            'filter_index' => new Zend_Db_Expr("CONCAT(main_table.quantity, \' \', currency_table.caption)"),
        ));

        $this->addColumn('comments', array(
            'header' => Mage::helper('tbtmilestone')->__('Comments/Notes'),
            'width'  => '250px',
            'index'  => 'comments'
        ));

        $statuses = Mage::getSingleton('rewards/transfer_status')->getOptionArray();
        $this->addColumn('status_id', array(
            'header'  => Mage::helper('tbtmilestone')->__('Status'),
            'align'   => 'left',
            'width'   => '80px',
            'index'   => 'status',
            'type'    => 'options',
            'options' => $statuses
        ));

        return parent::_prepareColumns();
    }

    protected function _getCurrentLog()
    {
        return Mage::registry('current_milestone_rule_log');
    }

    public function getTabLabel()
    {
        return $this->__("Points Transfers");
    }

    public function getTabTitle()
    {
        return $this->__("Points Transfers");
    }

    public function canShowTab()
    {
        if (!Mage::helper('tbtcommon')->getLoyaltyHelper('rewards')->isValid()) {
            return false;
        }

        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
