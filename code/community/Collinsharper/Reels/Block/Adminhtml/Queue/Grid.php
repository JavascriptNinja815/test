<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Queue/Grid.php
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
 * Collinsharper_Reels_Block_Adminhtml_Queue_Grid
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
class Collinsharper_Reels_Block_Adminhtml_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('queueGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('chreels/printqueue')->getCollection();
       /*  $collection->getSelect()
            ->join( array('ce2' => 'customer_entity'), 'ce2.entity_id=main_table.customer_id', array('email' => 'email'));
 */

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('chreels')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'entity_id',
        ));

        $this->addColumn('customer_id', array(
            'header'    => Mage::helper('chreels')->__('Customer ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'reel_id',
        ));
		
        $this->addColumn('reel_id', array(
            'header'    => Mage::helper('chreels')->__('Reel ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'reel_id',
        ));
		
        $this->addColumn('print_filename', array(
            'header'    => Mage::helper('chreels')->__('Print Filename'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'print_filename',
        ));

        $this->addColumn('final_reel_filename', array(
            'header'    => Mage::helper('chreels')->__('Reel Name'),
            'align'     =>'left',
            'index'     => 'final_reel_filename',
        ));
		
        $this->addColumn('order_id', array(
            'header'    => Mage::helper('chreels')->__('Order Id'),
            'align'     =>'left',
            'index'     => 'order_id',
        ));
		
		$this->addColumn('order_item_id', array(
            'header'    => Mage::helper('chreels')->__('Order Item Id'),
            'align'     =>'left',
            'index'     => 'order_item_id',
        ));
		
		$this->addColumn('qty', array(
            'header'    => Mage::helper('chreels')->__('Quantity'),
            'align'     =>'left',
            'index'     => 'qty',
        ));
		
		$this->addColumn('status', array(
            'header'    => Mage::helper('chreels')->__('Status'),
            'align'     =>'left',
            'index'     => 'status',
        ));
		        $this->addColumn('final_reel_filename', array(
            'header'    => Mage::helper('chreels')->__('Reel Name'),
            'align'     =>'left',
            'index'     => 'final_reel_filename',
        ));
		
        $this->addColumn('created_at', array(
              'header'    => Mage::helper('chreels')->__('Creation Date'),
              'width'     => '150px',
              'index'     => 'created_at',
        ));

        $this->addColumn('printed_at', array(
              'header'    => Mage::helper('chreels')->__('Print Date'),
              'width'     => '150px',
              'index'     => 'printed_at',
        ));

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


        $this->getMassactionBlock()->addItem('queueprint', array(
            'label'    => Mage::helper('chreels')->__('Queue Printing'),
            'url'      => $this->getUrl('*/*/queuePrint'),
            'confirm'  => Mage::helper('chreels')->__('This will queue printing 1 reel of each selected. Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('entity_id')));
    }

}