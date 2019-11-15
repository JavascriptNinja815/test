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
 * Manage Transfer Edit Tab Grid Transfers
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Adminhtml_Sales_Order_View_Tab_Points
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * @var TBT_Rewards_Model_Mysql4_Transfer_Collection */
    /**
     * Transfers Collection
     * @var TBT_Rewards_Model_Mysql4_Transfer_Collection
     */
    protected $collection = null;

    /**
     * Flag uses to mark columns already defined
     * @var boolean
     */
    protected $columnsAreSet = false;

    /**
     * Main Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('points');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir ('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * Getter for Store based on request or magento init
     * @return type
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
     * Prepare Collection
     * @return TBT_Rewards_Block_Adminhtml_Sales_Order_View_Tab_Points
     */
    protected function _prepareCollection()
    {
        if ($this->collection == null) {
            $this->collection = $this->getOrder()->getAssociatedTransfers();
        }
        
        $store = $this->_getStore();
        
        if ($store->getId()) {
            $this->collection->addStoreFilter($store);
        }
        
        $this->collection->selectFullCustomerName('fullcustomername');
        $this->collection->selectPointsCaption('points');
        
        $this->setCollection($this->collection);
        
        return parent::_prepareCollection();
    }

    /**
     * After load collection add revoked transfers
     * TODO: implement filters to work for revoked transfers
     */
    protected function _afterLoadCollection()
    {
        parent::_afterLoadCollection();
        
        /* include any transfers that have revoked the original transfers */
        $revokers = $this->getCollection()->selectRevokerTransfers();
        $revokers->selectPointsCaption('points');

        foreach ($revokers as $revoker) {
            $this->getCollection()->addItem($revoker);
        }
    }
    
    protected function _prepareColumns()
    {
        if ($this->columnsAreSet) {
            return parent::_prepareColumns();
        } else {
            $this->columnsAreSet = true;
        }
        
        $this->addColumn(
            'transfer_id',
            array(
                'header' => Mage::helper('rewards')->__('ID'),
                'align' => 'left',
                'width' => '36px',
                'index' => 'rewards_transfer_id',
            )
        );
        
        $this->addColumn(
            'points',
            array(
                'header' => Mage::helper('rewards')->__('Points'),
                'align' => 'left',
                'width' => '70px',
                'index' => 'points',
                'filter_index' => new Zend_Db_Expr("CONCAT(main_table.quantity, ' ', currency_table.caption)")
            )
        );
        
        $reasons = Mage::helper('rewards/transfer_reason')->getAllReasons();
        $this->addColumn(
            'reason',
            array(
                'header' => Mage::helper('rewards')->__('Reason'),
                'align' => 'left',
                'width' => '100px',
                'index' => 'reason_id',
                'type' => 'options',
                'options' => $reasons
            )
        );
        
        $this->addColumn(
            'comments',
            array(
                'header' => Mage::helper('rewards')->__('Comments/Notes'),
                'width' => '250px',
                'index' => 'comments'
            )
        );
        
        $statuses = Mage::getSingleton('rewards/transfer_status')->getOptionArray();

        $this->addColumn(
            'status_id',
            array(
                'header' => Mage::helper('rewards')->__('Status ID'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'status_id',
                'type' => 'options',
                'options' => $statuses
            )
        );
        
        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('rewards')->__('Action'),
                'width' => '50px',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array('caption' => Mage::helper('rewards')->__('View'),
                        'url' => array('base' => 'adminhtml/manage_transfer/edit/' . 'module/adminhtml/controller/sales_order/action/view/' . 'order_id/' . $this->getOrderId()),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true
            )
        );
        
        return parent::_prepareColumns();
    }
    
    /**
     * Fetch Grid Url
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'adminhtml/manage_sales_order/transfersGrid',
            array ('order_id' => $this->getOrderId(), '_current' => true)
        );
    }
    
    /**
     * Retrieve available order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->getData ( 'order' );
        }

        if (Mage::registry('current_order')) {
            return Mage::registry('current_order');
        }

        if (Mage::registry ('order')) {
            return Mage::registry('order');
        }

        Mage::throwException(Mage::helper('sales')->__('Can\'t get order instance'));
    }
    
    /**
     * Fetches the order id currently open.
     *
     * @return int
     */
    public function getOrderId()
    {
        $o = $this->getOrder();

        if ($o->getId()) {
            $oid = $o->getId();
        } else {
            $oid = $this->getRequest()->getParam('order_id');
        }

        return $oid;
    }
    
    /**
     * Get Tab Label
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__("Points & Rewards");
    }

    /**
     * Get Tab Title
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__("Points & Rewards");
    }

    /**
     * Grid Can Show Validator
     * @return boolean
     */
    public function canShowTab()
    {
        if (!Mage::helper('tbtcommon')->getLoyaltyHelper('rewards')->isValid()) {
            return false;
        }

        return true;
    }

    /**
     * Grid Is Hidden Validator
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}
