<?php

class Collinsharper_Ordericons_Block_Adminhtml_Renderer_Ordericons extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $html = '';
        $x = new Collinsharper_Ordericons_Helper_Data;
        $mediaPath = ($x->getMediaUrl() . DS) ;
        $images = array();

        if($row instanceof Collinsharper_Ordericons_Model_Chicons) {

            $images[$row->getData('name')] = $mediaPath . $row->getData('image');

        } else {

            $images = Mage::helper('chordericons')->getOrderIcons($row);

        }

        foreach($images as $title => $image) {
            $html .= "<h4  class=\"ordericons image chordericons_{$row->getId()}\" style=\"float:left\" ><img src=\"{$image}\" title=\"{$title}\" alt=\"{$title}\" style=\"max-height: 18px;\" /> </h4> &nbsp;\n";
        }

        if(get_class($row) == 'Mage_Sales_Model_Order') {
            $html .= $this->__('<a href="javascript:void(0);" onClick="chOpenMessagePopup(%s);">%s</a>',$row->getId(),'Change Icons');
        }
        return $html;
    }
}
