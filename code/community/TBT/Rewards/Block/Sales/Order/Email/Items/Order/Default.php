<?php

class TBT_Rewards_Block_Sales_Order_Email_Items_Order_Default extends Mage_Sales_Block_Order_Email_Items_Order_Default
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
