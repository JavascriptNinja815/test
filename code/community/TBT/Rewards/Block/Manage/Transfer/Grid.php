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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Manage Transfer Grid
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Transfer_Grid extends Mage_Adminhtml_Block_Widget_Grid 
{
    protected $collection = null;
    protected $columnsAreSet = false;
	
    public function __construct() 
    {
        parent::__construct();
        $this->setId('transfersGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _getStore() 
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        if ($this->collection == null) {
            $this->collection = Mage::getModel('rewards/transfer')->getCollection ();
        }

        $store = $this->_getStore();
        if ($store->getId()) {
            $this->collection->addStoreFilter($store);
        }

        $this->collection->selectFullCustomerName('fullcustomername');
        $this->collection->selectCustomerEmail();
        $this->collection->selectIncrementIdOnOrders();
        $this->collection->selectPointsCaption('points');

        $this->setCollection($this->collection);
        
        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('XML'));

        return parent::_prepareCollection();
    }

    protected function _prepareColumns() 
    {
        if ($this->columnsAreSet) {
            return parent::_prepareColumns();
        } else {
            $this->columnsAreSet = true;
        }

        $this->addColumn('rewards_transfer_id', array(
            'header' => Mage::helper('rewards')->__('ID'), 
            'align' => 'right', 
            'width' => '36px', 
            'index' => 'rewards_transfer_id'
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('rewards')->__('Created Time'), 
            'width' => '40px', 
            'type' => 'datetime', 
            'index' => 'created_at',
            'filter_index' => new Zend_Db_Expr("main_table.created_at")
        ));

        $this->addColumn('points', array(
            'header' => Mage::helper('rewards')->__('Points'), 
            'align' => 'left', 
            'width' => '70px', 
            'index' => 'points', 
            'filter_index' => "main_table.quantity"
        ));
        
        $this->addColumn('reference_id', array(
            'header' => Mage::helper('rewards')->__('Reference'), 
            'align' => 'left', 
            'width' => '40px', 
            'index' => 'reference_id',
            'renderer'  => 'TBT_Rewards_Block_Manage_Transfer_Renderer_Reference',
            'filter_condition_callback' => array($this, 'filterReferenceId')
        ));

        $this->addColumn('fullcustomername', array(
            'header' => Mage::helper('rewards')->__('Customer Name'), 
            'align' => 'left', 
            'width' => '80px', 
            'index' => 'fullcustomername', 
            'filter_index' => new Zend_Db_Expr("CONCAT(customer_firstname_table.value, ' ', customer_lastname_table.value)")
        ));
        
        $this->addColumn('email', array(
            'header' => Mage::helper('rewards')->__('Customer Email'), 
            'width' => '40px', 
            'type' => 'text', 
            'index' => 'email'
        ));

        $reasons = Mage::helper('rewards/transfer_reason')->getAllReasons();
        $this->addColumn('reason', array(
            'header' => Mage::helper('rewards')->__('Reason'), 
            'align' => 'left', 
            'width' => '100px', 
            'index' => 'reason_id', 
            'type' => 'options', 
            'options' => $reasons
        ));

        $this->addColumn('comments', array(
            'header' => Mage::helper('rewards')->__('Comments/Notes'), 
            'width' => '250px', 
            'index' => 'comments'
        ));

        $statuses = Mage::getSingleton ( 'rewards/transfer_status' )->getOptionArray();
        $this->addColumn('status_id', array(
            'header' => Mage::helper('rewards')->__('Status ID'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'status_id',
            'type' => 'options',
            'options' => $statuses
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('rewards')->__('Action'), 
            'width' => '100', 
            'type' => 'action', 
            'getter' => 'getId', 
            'actions' => array(array(
                'caption' => Mage::helper( 'rewards' )->__('View'), 
                'url' => array('base' => '*/*/edit'), 'field' => 'id'
            )), 
            'filter' => false, 
            'sortable' => false, 
            'index' => 'stores', 
            'is_system' => true
        ));

        return parent::_prepareColumns();
    }
    
    /**
     * Include Increment Id In filter
     * 
     * @param TBT_Rewards_Model_Mysql4_Transfer_Collection $collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return $this
     */
    protected function filterReferenceId($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if (empty($value)) {
            return $this;
        }
        
        $collection->addFieldToFilter(
            array('main_table.reference_id', 'sales_order.increment_id'),
            array(
                array('like' => "%{$value}%"), 
                array('like' => "%{$value}%")
            )
        );
                
        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('rewards_transfer_id');
        $this->getMassactionBlock()->setFormFieldName('transfers');
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('rewards')->__('Delete'), 
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('rewards')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('rewards/transfer_status')->genSelectableStatuses();
        $this->getMassactionBlock()->addItem('status_id', array(
            'label' => Mage::helper('rewards')->__('Change status'), 
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)), 
            'additional' => array(
                'visibility' => array(
                    'name' => 'status_id', 
                    'type' => 'select', 
                    'class' => 'required-entry', 
                    'label' => Mage::helper('rewards')->__('Status ID'), 
                    'values' => $statuses
                )
            )
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}

