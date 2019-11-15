<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Giftcardordercolumn extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{


    public function render(Varien_Object $row)
    {
       // $value =  unserialize($row->getData($this->getColumn()->getIndex()));
        $value = $row->getData($this->getColumn()->getIndex());
        //mage::log(__METHOD__ . __LINE__ . " and " . print_r($value,1));

        return $value['message_data'];
    }


}
