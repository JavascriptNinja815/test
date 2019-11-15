<?php
/**
 * Collinsharper/Frames/Block/Adminhtml/Frames/Grid.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Frames_Block_Adminhtml_Frames_Grid
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Frames_Block_Adminhtml_Frames_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('framesGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('chframes/frames')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('frame_id', array(
            'header'    => Mage::helper('chframes')->__('Frame ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'frame_id',
        ));

        $this->addColumn('frame_name', array(
            'header'    => Mage::helper('chframes')->__('Name'),
            'align'     =>'left',
            'index'     => 'frame_name',
        ));

        $this->addColumn('created_at', array(
              'header'    => Mage::helper('chframes')->__('Creation Date'),
              'width'     => '150px',
              'index'     => 'created_at',
        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('chframes')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getFrameId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('chframes')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'frame_id',
                'is_system' => true,
            ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('frameIds');
        $this->getMassactionBlock()->setFormFieldName('frames');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('chframes')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('chframes')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getData('frame_id')));
    }

}
