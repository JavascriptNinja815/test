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
 * @package    [TBT_Reports]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Loyalty Orders Grid
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Block_Adminhtml_Loyalty_PurchaseRate_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Basic constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('tbtreports_loyalty_purchaseRate_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }
    
    /**
     * We set the 'period' by default to 30 days, starting from "now"
     * @return array
     */
    protected function getDefaultPeriodFilter()
    {
        $datetimeHelper = Mage::helper('rewards/datetime');
        $to = $datetimeHelper->getZendDate(null, true);
        $from = $datetimeHelper->getZendDate(null, true);
        $from = $datetimeHelper->addOffsetToDate($from, -(30 * 24 * 60 * 60));

        return array(
            'from' => $from,
            'to' => $to
        );
    }
    
    /**
     * Prepare period filter
     * - from period: is converted from local timezone to UTC
     * - to period: now magento is doing some "magic" here that we need to fix
     *      - magento is adding a day and subtracting a second which we need to revert
     *      - magento set this date to UTC already, so we don't need to do it again
     *      - @see Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Datetime::getValue()
     * 
     * @param array $value
     * @param array $value
     */
    protected function prepareDates($value)
    {
        if (!empty($value['from'])) {
            $from = $value['from'];
            $from->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
        }
        
        if (!empty($value['to'])) {
            $to = $value['to'];
            $to->subDay(1)->addSecond(1);
        }
        
        return $value;
    }

    /**
     * Prepare collection
     * @return $this
     */
    protected function _prepareCollection()
    {
        /* Inititalize Filters */
        $filter = $this->getParam($this->getVarNameFilter(), null);
        if (is_null($filter)) {
            $filter = $this->_defaultFilter;
        }

        $filters = array();
        if (is_string($filter)) {
            $data = $this->helper('adminhtml')->prepareFilterString($filter);
            $filters = $data;
        } elseif ($filter && is_array($filter)) {
            $filters = $filter;
        } elseif (0 !== sizeof($this->_defaultFilter)) {
            $filters = $this->_defaultFilter;
        }
        
        if (empty($filters['period']['from']) && empty($filters['period']['to'])) {
            $filters['period'] = $this->getDefaultPeriodFilter();
        }
        
        $filters['period_start'] = $filters['period'];
        $filters['period_end'] = $filters['period'];
        $this->_setFilterValues($filters);
        
        /* Initialize Collection */
        $collection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname');
        
        $readAdapter = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $readAdapter->select()
            ->from(array('orders' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid')))
            ->columns(array(
                'orders_count' => new Zend_Db_Expr('COUNT(*)'),
                'average_order_amount' => new Zend_Db_Expr('AVG(base_grand_total)')
            ))->where('status = ?', 'complete')
            ->group('orders.customer_id');

        /* Period Filter */
        $periodFilter = $this->getColumn('period')->getFilter()->getValue();
        $periodFilter = $this->prepareDates($periodFilter);
        
        if (isset($periodFilter['from'])) {
            $select->where('orders.created_at >= ?', $periodFilter['from']->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND));
        } 
        
        if (isset($periodFilter['to'])) {
            $select->where('orders.created_at <= ?', $periodFilter['to']->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND));
        }
        
        /* Order Types Filter */
        $reportCode = Mage::app()->getRequest()->getParam('report_code');
        $orderTypes = $this->getColumn('order_type')->getFilter()->getValue();
        $orderReasonId = Mage::helper('rewards/transfer_reason')->getReasonId('order');

        if (!$orderTypes && $reportCode) {
            if ($reportCode == 'revenue_loyalty') {
                $this->getColumn('order_type')->getFilter()->setValue(TBT_Reports_Model_Source_OrderType::TYPE_LOYALTY_ORDERS);
                $orderTypes = TBT_Reports_Model_Source_OrderType::TYPE_LOYALTY_ORDERS;
            }
            
            if ($reportCode == 'revenue_referred_customers') {
                $this->getColumn('order_type')->getFilter()->setValue(TBT_Reports_Model_Source_OrderType::TYPE_ORDERS_FROM_REFERRALS);
                $orderTypes = TBT_Reports_Model_Source_OrderType::TYPE_ORDERS_FROM_REFERRALS;
            }
        }
                
        switch ($orderTypes) {
            case (TBT_Reports_Model_Source_OrderType::TYPE_ALL_ORDERS):
                break;
            case (TBT_Reports_Model_Source_OrderType::TYPE_ORDERS_WITH_EARNINGS):
                $select->joinInner(
                    array('transfers' => Mage::getSingleton('core/resource')->getTableName('rewards_transfer')),
                    'orders.entity_id = transfers.reference_id',
                    array())
                ->where('transfers.status_id = ?', TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED)
                ->where('transfers.reason_id = ?', $orderReasonId)
                ->where('transfers.quantity > ?', 0);
                
                break;
            case (TBT_Reports_Model_Source_OrderType::TYPE_ORDERS_WITH_SPENDINGS):
                $select->joinInner(
                    array('transfers' => Mage::getSingleton('core/resource')->getTableName('rewards_transfer')),
                    'orders.entity_id = transfers.reference_id',
                    array())
                ->where('transfers.status_id = ?', TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED)
                ->where('transfers.reason_id = ?', $orderReasonId)
                ->where('transfers.quantity < ?', 0);
                
                break;
            case (TBT_Reports_Model_Source_OrderType::TYPE_LOYALTY_ORDERS):
                $select->joinInner(
                    array('reports' => Mage::getSingleton('core/resource')->getTableName('rewards_reports_indexer_order')),
                    'orders.entity_id = reports.order_id',
                    array())
                ->where('reports.by_loyalty_customer = ?', 1);
                
                break;
            case (TBT_Reports_Model_Source_OrderType::TYPE_ORDERS_FROM_REFERRALS):
                $select->joinInner(
                    array('reports' => Mage::getSingleton('core/resource')->getTableName('rewards_reports_indexer_order')),
                    'orders.entity_id = reports.order_id',
                    array())
                ->where('reports.by_referred_customer = ?', 1);
                
                break;
        }
        
        /* Add subquery */
        $collection->getSelect()->joinLeft(
            array('orders_info' => new Zend_Db_Expr('('.$select.')')),
            'e.entity_id = orders_info.customer_id',
            array('orders_info.orders_count', 'orders_info.average_order_amount')
        );
        
         /* Orders Count Filter */
        $ordersCountFilter = $this->getColumn('orders_count')->getFilter()->getValue();
        
        if (isset($ordersCountFilter['from'])) {
            $collection->getSelect()->where('orders_info.orders_count >= ?', $ordersCountFilter['from']);
        } 
        
        if (isset($ordersCountFilter['to'])) {
            $collection->getSelect()->where('orders_info.orders_count <= ?', $ordersCountFilter['to']);
        }
        
        /* Average Order Amount Filter */
        $averageOrderAmountFilter = $this->getColumn('average_order_amount')->getFilter()->getValue();
        
        if (isset($averageOrderAmountFilter['from'])) {
            $averageOrderAmountFilter['from'] = preg_replace('/[^0-9.]/', '', $averageOrderAmountFilter['from']);
            $collection->getSelect()->where('orders_info.average_order_amount >= ?', $averageOrderAmountFilter['from']);
        } 
        
        if (isset($averageOrderAmountFilter['to'])) {
            $averageOrderAmountFilter['to'] = preg_replace('/[^0-9.]/', '', $averageOrderAmountFilter['to']);
            $collection->getSelect()->where('orders_info.average_order_amount <= ?', $averageOrderAmountFilter['to']);
        }

        $collection->addFilterToMap('orders_count', 'orders_info.orders_count');
        $collection->addFilterToMap('average_order_amount', 'orders_info.average_order_amount');
        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Adding columns
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('sales')->__('Customer ID'),
            'width'     => '80px',
            'type'      => 'text',
            'index'     => 'entity_id'
        ));

        $this->addColumn('firstname', array(
            'header'    => Mage::helper('sales')->__('First Name'),
            'index'     => 'firstname'
        ));

        $this->addColumn('lastname', array(
            'header'    => Mage::helper('sales')->__('Last Name'),
            'index'     => 'lastname'
        ));
        
        $this->addColumn('email', array(
            'header'    => Mage::helper('sales')->__('Email'),
            'index'     => 'email'
        ));

        if ($this->_isExport) {
            $this->addColumn('period_start', array(
                'header'    => Mage::helper('customer')->__('From Period'),
                'align'     => 'left',
                'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Period_Start',
                'filter_condition_callback' => array($this, 'silentFilter'),
                'type'      => 'datetime',
                'sortable'  => false
            ));

            $this->addColumn('period_end', array(
                'header'    => Mage::helper('customer')->__('To Period'),
                'align'     => 'left',
                'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Period_End',
                'filter_condition_callback' => array($this, 'silentFilter'),
                'type'      => 'datetime',
                'sortable'  => false
            ));
            
            $this->addColumn('period', array(
                'header'    => Mage::helper('customer')->__('Period'),
                'align'     => 'left',
                'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Period',
                'filter_condition_callback' => array($this, 'silentFilter'),
                'type'      => 'datetime',
                'is_system' => true, /* This will hide the column in exports */
                'sortable'  => false
            ));
        } else {
            $this->addColumn('period', array(
                'header'    => Mage::helper('customer')->__('Period'),
                'align'     => 'left',
                'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Period',
                'filter_condition_callback' => array($this, 'silentFilter'),
                'type'      => 'datetime',
                'sortable'  => false
            ));
        }
        
        $this->addColumn('orders_count', array(
            'header'    => Mage::helper('customer')->__('Number of Orders'),
            'align'     => 'left',
            'width'     => '40px',
            'index'     => 'orders_count',
            'type'      => 'number',
            'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Count',
            'filter_condition_callback' => array($this, 'silentFilter'),
            'sortable'  => true
        ));
        
        $this->addColumn('average_order_amount', array(
            'header'    => Mage::helper('customer')->__('Average of Orders'),
            'align'     => 'left',
            'width'     => '40px',
            'index'     => 'average_order_amount',
            'type'      => 'number',
            'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Currency',
            'filter_condition_callback' => array($this, 'silentFilter'),
            'sortable'  => true
        ));
        
        $this->addColumn('order_type', array(
            'header'    => Mage::helper('customer')->__('Order Types'),
            'align'     => 'left',
            'width'     => '40px',
            'type'      => 'options',
            'options'   => Mage::getModel('tbtreports/source_orderType')->getAllOptions(),
            'renderer'  => 'TBT_Reports_Block_Adminhtml_Loyalty_Renderer_Type',
            'filter_condition_callback' => array($this, 'silentFilter'),
            'sortable'  => false
        ));
        
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }
    
    /**
     * We are doing some filtering in "prepareCollection" ourselves. We need to stop 
     * the default magento filtering for these columns
     * 
     * @param type $collection
     * @param type $column
     * @return \TBT_Reports_Block_Adminhtml_Loyalty_PurchaseRate_Grid
     */
    public function silentFilter($collection, $column)
    {
        return $this;
    }
    
    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
            
            if ($columnIndex == 'orders_count' || $columnIndex == 'average_order_amount') {
                $collection->getSelect()->order($columnIndex . ' ' . strtoupper($column->getDir()));
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }
        
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}

