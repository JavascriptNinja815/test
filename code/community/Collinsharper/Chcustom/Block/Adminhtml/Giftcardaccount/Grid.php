<?php

class Collinsharper_Chcustom_Block_Adminhtml_Giftcardaccount_Grid extends Enterprise_GiftCardAccount_Block_Adminhtml_Giftcardaccount_Grid
{

    protected function _prepareColumns()
    {
        mage::log(__FILE__ . __LINE__);
        $ret = parent::_prepareColumns();

       $this->addColumn('notes',
            array(
                'header'        => Mage::helper('enterprise_giftcardaccount')->__('Notes'),
                'type'          => 'text',
                //'renderer'      => 'enterprise_giftcardaccount/adminhtml_widget_grid_column_renderer_currency',
                'index'         => 'notes',
                'class'         => ' "text-overflow"'
        ));
        return $ret;
    }
}
