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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Manage Currency Grid
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Currency_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	protected $collection = null;
	protected $columnsAreSet = false;

	public function __construct() {
		parent::__construct ();
		$this->setId ( 'currenciesGrid' );
		$this->setDefaultSort ( 'rewards_currency_id' );
		$this->setDefaultDir ( 'DESC' );
		$this->setSaveParametersInSession ( true );
	}

	protected function _getStore() {
		$storeId = ( int ) $this->getRequest ()->getParam ( 'store', 0 );
		return Mage::app ()->getStore ( $storeId );
	}

	protected function _prepareCollection() {
		if ($this->collection == null) {
                        $this->collection = Mage::getSingleton('rewards/currency')->getCollection ();
		}

		$store = $this->_getStore ();
		if ($store->getId ()) {
			$this->collection->addStoreFilter ( $store );
		}

		$this->setCollection ( $this->collection );

		//$this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
		//$this->addExportType('*/*/exportXml', Mage::helper('customer')->__('XML'));


		return parent::_prepareCollection ();
	}

	protected function _prepareColumns() {
		if ($this->columnsAreSet)
			return parent::_prepareColumns ();
		else
			$this->columnsAreSet = true;

		$this->addColumn ( 'rewards_currency_id', array ('header' => Mage::helper ( 'rewards' )->__ ( 'ID' ), 'align' => 'right', 'width' => '80px', 'index' => 'rewards_currency_id' ) );

		$this->addColumn ( 'active', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Is Active' ), 'align' => 'center', 'width' => '50px', 'type' => 'checkbox', 'index' => 'active', 'values' => array ('1' => true ) ) );

		$this->addColumn ( 'caption', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Name' ), 'align' => 'left', 'index' => 'caption' ) );

		/*        $this->addColumn('value', array(
            'header' => Mage::helper('rewards')->__('Value'),
            'align' => 'left',
            'width' => '80px',
            'type' => 'number',
            'index' => 'value',
        ));
*/
		$this->addColumn ( 'action', array ('header' => Mage::helper ( 'rewards' )->__ ( 'Action' ), 'width' => '100', 'type' => 'action', 'getter' => 'getId', 'actions' => array (array ('caption' => Mage::helper ( 'rewards' )->__ ( 'Edit' ), 'url' => array ('base' => '*/*/edit' ), 'field' => 'id' ) ), 'filter' => false, 'sortable' => false, 'index' => 'stores', 'is_system' => true ) );

		return parent::_prepareColumns ();
	}

	protected function _prepareMassaction() {
		return $this;
	}

	public function getRowUrl($row) {
		return $this->getUrl ( '*/*/edit', array ('id' => $row->getId () ) );
	}

}