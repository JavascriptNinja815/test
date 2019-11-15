<?php
class Collinsharper_Inquiry_Block_Adminhtml_Renderer_Ip extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if($value && strpos('.',$value) === false) {
            return long2ip($value);
        }
        return $value;
    }
}
