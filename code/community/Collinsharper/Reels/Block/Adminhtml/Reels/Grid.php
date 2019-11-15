<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Reels/Grid.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Reels_Block_Adminhtml_Reels_Grid
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Reels_Block_Adminhtml_Reels_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('reelsGrid');
        $this->setDefaultSort('main_table.entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('chreels/reels')->getCollection();
        $collection->getSelect()
 //           ->join( array('ce1' => 'customer_entity'), 'ce1.entity_id=main_table.seller_id', array('seller_email' => 'email'))
            ->join( array('ce2' => 'customer_entity'), 'ce2.entity_id=main_table.customer_id', array('email' => 'email'));


        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('chreels')->__('Reel ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'entity_id',
            'filter_index'     => 'main_table.entity_id',
        ));

        $this->addColumn('customer_id', array(
            'header'    => Mage::helper('chreels')->__('Customer ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'customer_id',
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('chreels')->__('Customer Email'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'email',
        ));

        $this->addColumn('reel_name', array(
            'header'    => Mage::helper('chreels')->__('Reel Name'),
            'align'     =>'left',
            'index'     => 'reel_name',
        ));

        $this->addColumn('created_at', array(
              'header'    => Mage::helper('chreels')->__('Creation Date'),
              'width'     => '150px',
              'index'     => 'created_at',
        ));

        $this->addColumn('updated_at', array(
              'header'    => Mage::helper('chreels')->__('Update Date'),
              'width'     => '150px',
              'index'     => 'updated_at',
        ));

        $this->addColumn('viewed_at', array(
              'header'    => Mage::helper('chreels')->__('Viewed Date'),
              'width'     => '150px',
              'index'     => 'viewed_at',
        ));

        $this->addColumn('platform', array(
            'header'    => Mage::helper('chreels')->__('Platform'),
            'width'     => '150px',
            'index'     => 'platform',
            'renderer'  => 'chreels/adminhtml_reels_grid_renderer_platform',
        ));
//
        $this->addColumn('status', array(
              'header'    => Mage::helper('chreels')->__('Status'),
              'width'     => '140px',
              'index'     => 'status',
            'type'  => 'options',

            'options' => Collinsharper_Reels_Model_Reels::getStatusOptions(),

        ));
//
//        $this->addColumn('print_qty', array(
//              'header'    => Mage::helper('chreels')->__('Print QTY'),
//              'width'     => '150px',
//              'default_value'     => 0,
//              'index'     => 'print_qty',
//            'renderer'  => 'Collinsharper_Reels_Block_Adminhtml_Renderer_Printqty',
//
//        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('chreels')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getEntityId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('chreels')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    ),

                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'entity_id',
                'is_system' => true,
            ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('reelIds');
        $this->getMassactionBlock()->setFormFieldName('reels');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('chreels')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('chreels')->__('Are you sure?')
        ));


        //$this->getMassactionBlock()->addItem('queueprint', array(
        //    'label'    => Mage::helper('chreels')->__('Queue Printing'),
        //    'url'      => $this->getUrl('*/*/queuePrint'),
        //    'confirm'  => Mage::helper('chreels')->__('This will queue printing 1 reel of each selected. Are you sure?')
        //));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('entity_id')));
    }

}
