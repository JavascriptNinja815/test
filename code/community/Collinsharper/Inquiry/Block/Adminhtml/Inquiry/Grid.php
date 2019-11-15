<?php
class Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('inquiryGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        //$this->_removeButton('add');

    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('chinquiry/inquiry')->getCollection();
        /* @var $collection Collinsharper_Inquiry_Model_Resource_Inquiry_Collection */
        //$collection->setFirstStoreFlag(true);


        // TODO we should  only show NEW by default.
        /*
        $canShowArchive = false;
        //check if there is a filter.
        $filter = $this->getParam($this->getVarNameFilter(), null);
        //if there is a filter, check if the filter contains the status
        if (!is_null($filter))  {
            //decode the filter
            $filter = $this->helper('adminhtml')->prepareFilterString($filter);
            if (isset($filter['status'])) {
                //if there is a filter by status you may need to show the archive items
                $canShowArchive = true;
            }
        }
        //if $canShowArchive is false, just filter the collection by status not equal to 'archive'
        if (!$canShowArchive) {
            $collection->addFieldToFilter('status', array('neq'=>3));
        }
*/
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('chinquiry')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'entity_id',
        ));

     if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }


   $this->addColumn('inquiry_type', array(
            'header' => Mage::helper('sales')->__('Type'),
            'index' => 'inquiry_type',
            'type'  => 'options',
            //'width' => '70px',
            'options' => Mage::getSingleton('chinquiry/source_inquirytype')->getOptions(),
        ));


        $this->addColumn('name', array(
            'header'    => Mage::helper('chinquiry')->__('Email'),
            'align'     =>'left',
            'index'     => 'name',
        ));

        $this->addColumn('customer_id', array(
            'header'    => Mage::helper('chinquiry')->__('Customer ID'),
            'align'     =>'left',
            'index'     => 'customer_id',
        ));

/*
        $this->addColumn('ip', array(
            'header'    => Mage::helper('chinquiry')->__('IP'),
            'align'     =>'left',
            'index'     => 'ip',
            'renderer'  => 'Collinsharper_Inquiry_Block_Adminhtml_Renderer_Ip',

        ));

        $this->addColumn('quote_id', array(
            'header'    => Mage::helper('chinquiry')->__('Quote ID'),
            'align'     =>'left',
            'index'     => 'quote_id',
        ));

        $this->addColumn('order_id', array(
            'header'    => Mage::helper('chinquiry')->__('Order ID'),
            'align'     =>'left',
            'index'     => 'order_id',
        ));
*/


   $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            //'width' => '70px',
            'options' => Mage::getSingleton('chinquiry/source_status')->getOptions(),
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Created At'),
            'index' => 'created_at',
       //     'type'  => 'options',
            //'width' => '70px',
      //      'options' => Mage::getSingleton('chinquiry/source_status')->getOptions(),
        ));



        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('chinquiry')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('chinquiry')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'entity_id',
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
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('chinquirys');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('chinquiry')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('chinquiry')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('chinquiry')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('chinquiry')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('entity_id')));
    }

}
