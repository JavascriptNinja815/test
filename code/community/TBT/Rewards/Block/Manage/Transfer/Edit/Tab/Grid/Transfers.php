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
class TBT_Rewards_Block_Manage_Transfer_Edit_Tab_Grid_Transfers extends Mage_Adminhtml_Block_Widget_Grid 
{
    protected $collection = null;
    protected $columnsAreSet = false;

    public function __construct() 
    {
        parent::__construct ();
        $this->setId ( 'transfersGrid' );
        $this->setDefaultSort ( 'created_at' );
        $this->setDefaultDir ( 'DESC' );
        $this->setUseAjax ( true );
        $this->setSaveParametersInSession ( true );
    }

    /**
     * Retirve currently edited product model
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _getTransfer() 
    {
        return Mage::registry ( 'transfer_data' );
    }

    protected function _getStore() 
    {
        $storeId = ( int ) $this->getRequest ()->getParam ( 'store', 0 );
        return Mage::app ()->getStore ( $storeId );
    }

    protected function _addColumnFilterToCollection($column) 
    {
        /* Set custom filter for in product flag */
        if ($column->getId () == 'assigned_transfer') {
            $referenceIds = $this->_getSelectedTransfers ();
            if (empty ( $referenceIds )) {
                $referenceIds = 0;
            }
            if ($column->getFilter ()->getValue ()) {
                $this->getCollection ()
                    ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('revoke'))
                    ->addFieldToFilter('reference_id', array('in' => $referenceIds));
            } elseif ($referenceIds) {
                $this->getCollection ()
                    ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('revoke'))
                    ->addFieldToFilter('reference_id', array('nin' => $referenceIds));
            }
        } else {
            parent::_addColumnFilterToCollection ( $column );
        }
        
        return $this;
    }

    protected function _prepareCollection() 
    {
        if ($this->collection == null) {
            $this->collection = Mage::getModel ( 'rewards/transfer' )->getCollection ();
        }

        $store = $this->_getStore ();
        if ($store->getId ()) {
            $this->collection->addStoreFilter ( $store );
        }

        $this->collection->selectFullCustomerName ( 'fullcustomername' );
        $this->collection->selectPointsCaption ( 'points' );
        $this->setCollection ( $this->collection );

        $this->addExportType ( '*/*/exportCsv', Mage::helper ( 'customer' )->__ ( 'CSV' ) );
        $this->addExportType ( '*/*/exportXml', Mage::helper ( 'customer' )->__ ( 'XML' ) );

        return parent::_prepareCollection ();
    }

    protected function _prepareLayout() 
    {
        $this->setChild ( 'clear_selections_button', $this->getLayout ()->createBlock ( 'adminhtml/widget_button' )->setData ( array ('label' => Mage::helper ( 'adminhtml' )->__ ( 'Clear Selections' ), 'onclick' => 'clearGridSelections(\'reference_transfer_id\')' ) ) );
        return parent::_prepareLayout ();
    }

    public function getClearSelectionsButtonHtml() 
    {
        return $this->getChildHtml ( 'clear_selections_button' );
    }

    public function getMainButtonsHtml() 
    {
        return $this->getClearSelectionsButtonHtml () . parent::getMainButtonsHtml ();
    }

    protected function _prepareColumns() 
    {
        if ($this->columnsAreSet) {
            return parent::_prepareColumns ();
        } else {
            $this->columnsAreSet = true;
        }

        $this->addColumn ( 'assigned_transfer', array ('header_css_class' => 'a-center', 'header' => Mage::helper ( 'adminhtml' )->__ ( 'Origin' ), 'type' => 'radio', 'html_name' => 'transfer_id', 'values' => $this->_getSelectedTransfers (), 'align' => 'center', 'index' => 'rewards_transfer_id', 'filter_index' => 'rewards_transfer_id' ) );
        $this->addColumn ( 'transfer_id', array ('header' => Mage::helper ( 'rewards' )->__ ( 'ID' ), 'align' => 'right', 'width' => '36px', 'index' => 'rewards_transfer_id' ) );
        $this->addColumn ( 'created_at', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Created Time' ), 'width' => '40px', 'type' => 'datetime', 'index' => 'created_at' ) );
        $this->addColumn ( 'points', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Points' ), 'align' => 'left', 'width' => '70px', 'index' => 'points' ) );

        $reasons = Mage::helper('rewards/transfer_reason')->getAllReasons();
        $this->addColumn ( 'reason', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Reason' ), 'align' => 'left', 'width' => '100px', 'index' => 'reason_id', 'type' => 'options', 'options' => $reasons ) );
        $this->addColumn ( 'comments', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Comments/Notes' ), 'width' => '250px', 'index' => 'comments' ) );

        $statuses = Mage::getSingleton ( 'rewards/transfer_status' )->getOptionArray ();
        $this->addColumn ( 'status_id', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Status ID' ), 'align' => 'left', 'width' => '80px', 'index' => 'status_id', 'type' => 'options', 'options' => $statuses ) );
        $this->addColumn ( 'action', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Action' ), 'width' => '100', 'type' => 'action', 'getter' => 'getId', 'actions' => array (array ('caption' => Mage::helper ( 'rewards' )->__ ( 'View' ), 'url' => array ('base' => '*/*/edit' ), 'field' => 'id' ) ), 'filter' => false, 'sortable' => false, 'index' => 'stores', 'is_system' => true ) );

        return parent::_prepareColumns ();
    }

    public function getGridUrl() 
    {
        return $this->getUrl ( '*/*/transfersGrid', array ('id' => $this->_getTransfer ()->getId () ) );
    }

    protected function _getSelectedTransfers() 
    {
        if (Mage::getSingleton ( 'adminhtml/session' )->getTransferData ()) {
            $formData = Mage::getSingleton ( 'adminhtml/session' )->getTransferData ();
            $transferIds = isset ( $formData ['reference_id'] ) ? $formData ['reference_id'] : array ();
        } elseif (Mage::registry ( 'transfer_data' )->getData ()) {
            $formData = Mage::registry ( 'transfer_data' )->getData ();
            $transferIds = isset ( $formData ['reference_id'] ) ? $formData ['reference_id'] : array ();
        } elseif ($this->getRequest ()->getPost ( 'transfer_id' )) {
            $transferIds = $this->getRequest ()->getPost ( 'transfer_id', null );
        } else {
            $transferIds = array ();
        }
        
        if (! is_array ( $transferIds ) && ( int ) $transferIds > 0) {
            $transferIds = array ($transferIds );
        }
        
        return $transferIds;
    }
}
