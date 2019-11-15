<?php

class Collinsharper_Ordericons_Block_Adminhtml_Chicons_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('chiconsGrid');
        $this->setDefaultSort('icon_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('chordericons/chicons')->getCollection();
        /* @var $collection Collinsharper_Ordericons_Model_Resource_chicons_Collection */
        //$collection->setFirstStoreFlag(true);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('icon_id', array(
            'header'    => Mage::helper('chordericons')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'icon_id',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('chordericons')->__('Name'),
            'align'     =>'left',
            'index'     => 'name',
        ));

        $this->addColumn('image', array(
            'header'    => Mage::helper('chordericons')->__('Image'),
            'align'     =>'left',
            'index'     => 'image',
            'renderer' => 'Collinsharper_Ordericons_Block_Adminhtml_Renderer_Ordericons',
        ));


		$this->addColumn('created_at', array(
              'header'    => Mage::helper('chordericons')->__('Creation Date'),
              'width'     => '150px',
              'index'     => 'created_at',
        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('chordericons')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getIconId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('chordericons')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'icon_id',
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
        $this->setMassactionIdField('icon_id');
        $this->getMassactionBlock()->setFormFieldName('chordericonss');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('chordericons')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('chordericons')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('icon_id')));
    }

}
