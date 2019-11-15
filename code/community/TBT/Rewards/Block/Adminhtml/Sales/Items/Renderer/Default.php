<?php

class TBT_Rewards_Block_Adminhtml_Sales_Items_Renderer_Default extends Mage_Adminhtml_Block_Sales_Items_Renderer_Default
{
    public function getIsPointsOnly()
    {
        $item = $this->getItem();
        return $this->_getHelper()->getIsPointsOnly($item);
    }

    public function getItemPointsPrice()
    {
        $item = $this->getItem();
        return $this->_getHelper()->getItemPointsPrice($item);
    }

    public function getItemSubtotalPointsPrice()
    {
        $item = $this->getItem();
        return $this->_getHelper()->getItemSubtotalPointsPrice($item);
    }

    protected function _getHelper()
    {
        return Mage::helper('rewards/renderer');
    }
}
