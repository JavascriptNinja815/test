<?php

class TBT_Milestone_Block_Manage_Rule_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        parent::_construct();

        $this->setId('milestoneRules');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);

        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('tbtmilestone/rule_collection');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('rule_id', array(
            'header' => $this->__("ID"),
            'index'  => 'rule_id',
            'width'  => '36px',
            'align'  => 'right'
        ));

        $this->addColumn('name', array(
            'header' => $this->__("Milestone Name"),
            'index'  => 'name'
        ));

        $this->addColumn('is_enabled', array(
            'header'  => $this->__("Status"),
            'index'   => 'is_enabled',
            'type'    => 'options',
            'width'   => '80px',
            'align'   => 'left',
            'options' => array(
                '1' => $this->__("Enabled"),
                '0' => $this->__("Disabled")
            )
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
