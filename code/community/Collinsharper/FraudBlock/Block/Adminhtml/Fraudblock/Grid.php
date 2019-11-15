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
class Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fraudblockGrid');
        $this->setDefaultSort('fraud_ban_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('fraudblock/fraudban')->getCollection();
        /* @var $collection Collinsharper_Fraudblock_Model_Resource_Fraudblock_Collection */
        //$collection->setFirstStoreFlag(true);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('fraud_ban_id', array(
            'header'    => Mage::helper('fraudblock')->__('Ban ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'fraud_ban_id',
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('fraudblock')->__('Email'),
            'align'     =>'left',
            'index'     => 'email',
        ));

        $this->addColumn('domain_a', array(
            'header'    => Mage::helper('fraudblock')->__('Domain 1'),
            'align'     =>'left',
            'index'     => 'domain_a',
        ));

        $this->addColumn('domain_c', array(
            'header'    => Mage::helper('fraudblock')->__('Domain 2'),
            'align'     =>'left',
            'index'     => 'domain_c',
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
                'getter'    => 'getFraudblockId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('fraudblock')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'fraud_ban_id',
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
        $this->setMassactionIdField('fraud_ban_id');
        $this->getMassactionBlock()->setFormFieldName('fraudblocks');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('fraudblock')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('fraudblock')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('fraud_ban_id')));
    }

}
