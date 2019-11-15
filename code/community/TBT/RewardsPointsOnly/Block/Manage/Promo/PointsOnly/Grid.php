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
 * Manage Promo Rewards PointsOnly Grid
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Manage_Promo_PointsOnly_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Main Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('promo_pointsonly_grid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare Rewards Points Only Rules Collection
     * @return TBT_RewardsPointsOnly_Model_Mysql4_Rule_Collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel ( 'rewardspointsonly/rule' )->getResourceCollection();

        $this->setCollection($collection);
        
        return parent::_prepareCollection();
    }

    /**
     * Prepare Grid Columns
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'rule_id',
            array(
                'header' => Mage::helper('rewardspointsonly')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'rule_id'
            )
        );

        $this->addColumn(
            'name',
            array(
                'header' => Mage::helper('rewardspointsonly')->__('Rule Name'),
                'align' => 'left',
                'index' => 'name'
            )
        );

        $this->addColumn(
            'from_date',
            array(
                'header' => Mage::helper('rewardspointsonly' )->__('Date Start'),
                'align' => 'left',
                'width' => '120px',
                'type' => 'date',
                'index' => 'from_date'
            )
        );

        $this->addColumn(
            'to_date',
            array(
                'header' => Mage::helper('rewardspointsonly')->__('Date Expire'),
                'align' => 'left',
                'width' => '120px',
                'type' => 'date',
                'default' => '--',
                'index' => 'to_date'
            )
        );

        $this->addColumn(
            'is_active',
            array(
                'header' => Mage::helper('rewardspointsonly')->__('Status'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'is_active',
                'type' => 'options',
                'renderer'  => 'TBT_Rewards_Block_Manage_Special_Renderer_Active',
                'options' => array(1 => 'Active', 0 => 'Inactive')
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getRuleId()));
    }
}
