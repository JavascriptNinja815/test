<?php
class Collinsharper_Reels_Block_Adminhtml_Renderer_Printqty extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        return '<input type="text" size="2" name="print_qty['  .  $row->getId() .  ']" value="1" />';

    }

}