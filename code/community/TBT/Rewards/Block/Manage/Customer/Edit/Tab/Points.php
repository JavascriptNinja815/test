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
 * Manage Transfer Edit Tab Grid Transfers
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Customer_Edit_Tab_Points extends Mage_Adminhtml_Block_Widget_Grid implements Mage_Adminhtml_Block_Widget_Tab_Interface 
{
    protected $collection = null;
    protected $columnsAreSet = false;

    public function __construct()
    {
        parent::__construct();
        $this->setId('transfersGridCustomerPoints');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        if ($this->collection == null) {
            $this->collection = Mage::getModel('rewards/transfer')->getCollection();
        }
        
        $customer = Mage::registry('current_customer');
        $this->collection->addFieldToFilter("main_table.customer_id", $customer->getId());
            
        $store = $this->_getStore();
        if ($store->getId()) {
            $this->collection->addStoreFilter($store);
        }

        $this->collection->selectIncrementIdOnOrders();
        $this->collection->selectPointsCaption('points');
        $this->setCollection($this->collection);
        
        return parent::_prepareCollection();
    }

    protected function _prepareLayout()
    {
        $url = $this->getUrl('adminhtml/manage_transfer/new', array(
            'module' => 'adminhtml', 
            'controller' => 'customer', 
            'action' => 'edit', 
            'customer_id' => $this->getCustomer()->getId()
        ));
        
        $this->setChild('new_transfer', $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
            'label' => Mage::helper('adminhtml')->__('New Transfer'), 
            'onclick' => "setLocation('{$url}')", 'class' => 'add')
        ));

        return parent::_prepareLayout();
    }

    public function getMainButtonsHtml()
    {
        $html = parent::getMainButtonsHtml();
        $html .= $this->getChildHtml('new_transfer');
        return $html;
    }

    protected function _prepareColumns()
    {
        if ($this->columnsAreSet) {
            return parent::_prepareColumns();
        } else {
            $this->columnsAreSet = true;
        }

        $this->addColumn('transfer_id', array(
            'header' => Mage::helper('rewards')->__('ID'), 
            'align' => 'left', 
            'width' => '36px', 
            'index' => 'rewards_transfer_id'
        ));
        
        $this->addColumn('created_at', array(
            'header' => Mage::helper('rewards')->__('Created Time'), 
            'width' => '40px', 
            'type' => 'datetime', 
            'index' => 'created_at'
        ));
        
        $this->addColumn('points', array(
            'header' => Mage::helper('rewards')->__('Points'), 
            'align' => 'left', 
            'width' => '70px', 
            'index' => 'points', 
            'filter_index' => new Zend_Db_Expr('CONCAT(main_table.quantity, \' \', currency_table.caption)')
        ));
        
        $this->addColumn('reference_id', array(
            'header' => Mage::helper('rewards')->__('Reference'), 
            'align' => 'left', 
            'width' => '40px', 
            'index' => 'reference_id',
            'renderer'  => 'TBT_Rewards_Block_Manage_Transfer_Renderer_Reference',
            'filter_condition_callback' => array($this, 'filterReferenceId')
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

        $statuses = Mage::getSingleton('rewards/transfer_status')->getOptionArray();
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
            'width' => '50px', 
            'type' => 'action', 
            'getter' => 'getId', 
            'actions' => array(array(
                'caption' => Mage::helper('rewards')->__('Edit'),
                'url' => array('base' => 'adminhtml/manage_transfer/edit/module/adminhtml/controller/customer/action/edit/customer_id/' . $this->getCustomer()->getId()), 
                'field' => 'id'
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

    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/manage_customer_edit/transfersGrid', array('customer_id' => $this->getCustomerId(), '_current' => true));
    }

    /**
     * Retrieve available customer
     * @return TBT_Rewards_Model_Customer
     */
    public function getCustomer() 
    {
        if ($this->hasCustomer()) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer($this->getData('customer'));
        }
        
        if (Mage::registry('current_customer')) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry('current_customer'));
        }
        
        if (Mage::registry('customer')) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry('customer'));
        }
        
        Mage::throwException(Mage::helper('customer')->__('Can\'t get customer instance'));
    }

    /**
     * Fetches the customer id currently open.
     * @return int
     */
    public function getCustomerId() 
    {
        $customer = $this->getCustomer();
        if ($customer->getId()) {
            $customerId = $customer->getId();
        } else {
            $customerId = $this->getRequest()->getParam('customer_id');
        }
        
        return $customerId;
    }

    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel() 
    {
        return $this->__ ( "Points & Rewards" );
    }

    public function getTabTitle()
    {
        return $this->__ ( "Points & Rewards" );
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden() 
    {
        return false;
    }
}

