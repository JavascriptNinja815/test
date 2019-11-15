<?php

class Collinsharper_Chcustom_Block_Adminhtml_Promo_Quote_Grid extends Mage_Adminhtml_Block_Promo_Quote_Grid
{

    protected function _prepareColumns()
    {
        mage::log(__METHOD__ . __LINE__);
        $ret = parent::_prepareColumns();
       $this->addColumn('notes',
            array(
                'header'        => Mage::helper('salesrule')->__('Notes'),
                'type'          => 'text',
                //'renderer'      => 'enterprise_giftcardaccount/adminhtml_widget_grid_column_renderer_currency',
                'index'         => 'notes',
                'class'         => ' "text-overflow"'
        ));

   
        $this->addColumn('myretail', array(
            'header'    => Mage::helper('salesrule')->__('MyReel Retail'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'myretail',
            'type'      => 'options',
            'options'   => array(
                1 => 'Yes',
                0 => 'No',
            ),
        ));


        return $ret;
    }
    

}
