<?php
/**
 * Collinsharper/Fraudblock/Block/Adminhtml/Fraudblock/Grid.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Fraudblock
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Grid
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Fraudblock
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Fraudblock_Block_Adminhtml_Faillog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('faillogGrid');
        $this->setDefaultSort('fraud_ban_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    //    $this->_removeButton('add');

    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('fraudblock/chfraudtrack')->getCollection();
        /* @var $collection Collinsharper_Fraudblock_Model_Resource_Fraudblock_Collection */
        //$collection->setFirstStoreFlag(true);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('fraud_tracking_id', array(
            'header'    => Mage::helper('fraudblock')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'fraud_tracking_id',
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('fraudblock')->__('Email'),
            'align'     =>'left',
            'index'     => 'email',
        ));

        $this->addColumn('customer_id', array(
            'header'    => Mage::helper('fraudblock')->__('Customer ID'),
            'align'     =>'left',
            'index'     => 'customer_id',
        ));
//
//        $this->addColumn('order_id', array(
//            'header'    => Mage::helper('fraudblock')->__('Order ID'),
//            'align'     =>'left',
//            'index'     => 'order_id',
//        ));

        $this->addColumn('ip', array(
            'header'    => Mage::helper('fraudblock')->__('IP'),
            'align'     =>'left',
            'index'     => 'ip',
            'renderer'  => 'Collinsharper_FraudBlock_Block_Adminhtml_Renderer_Ip',

        ));

        $this->addColumn('quote_id', array(
            'header'    => Mage::helper('fraudblock')->__('Quote ID'),
            'align'     =>'left',
            'index'     => 'quote_id',
        ));

        $this->addColumn('order_id', array(
            'header'    => Mage::helper('fraudblock')->__('Order ID'),
            'align'     =>'left',
            'index'     => 'order_id',
        ));

        if(Mage::getStoreConfig('sales/fraud_block/enable_browser_fingerprint')) {
            $this->addColumn('browser_hash', array(
                'header'    => Mage::helper('fraudblock')->__('Browser Hash'),
                'align'     =>'left',
                'index'     => 'browser_hash',
            ));

        }

        if(Mage::getStoreConfig('sales/fraud_block/hash_cards')) {
            $this->addColumn('cc_hash', array(
                'header'    => Mage::helper('fraudblock')->__('CC Hash'),
                'align'     =>'left',
                'index'     => 'cc_hash',
            ));

        }

        $this->addColumn('grand_total', array(
            'header'    => Mage::helper('fraudblock')->__('Total'),
            'align'     =>'left',
            'index'     => 'grand_total',
        ));

        $this->addColumn('blocked_reason', array(
            'header'    => Mage::helper('fraudblock')->__('Blocked Reason'),
            'align'     =>'left',
            'index'     => 'blocked_reason',
        ));

        $this->addColumn('was_failure', array(
            'header'    => Mage::helper('fraudblock')->__('Checkout Failure'),
            'align'     =>'left',
            'index'     => 'was_failure',
        ));

		$this->addColumn('created_at', array(
              'header'    => Mage::helper('fraudblock')->__('Creation Date'),
              'width'     => '150px',
              'index'     => 'created_at',
        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('fraudblock')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('fraudblock')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'fraud_tracking_id',
                'is_system' => true,
            ));


        return parent::_prepareColumns();
    }

    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('fraud_tracking_id');
        $this->getMassactionBlock()->setFormFieldName('fraudblocks');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('fraudblock')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('fraudblock')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('block_record', array(
            'label'    => Mage::helper('fraudblock')->__('Block Records'),
            'url'      => $this->getUrl('*/*/massBlock'),
            'confirm'  => Mage::helper('fraudblock')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('fraud_tracking_id')));
    }

}
