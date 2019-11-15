<?php

class TBT_Rewards_Block_Sales_Order_Email_Items_Order_Grouped extends TBT_Rewards_Block_Sales_Order_Email_Items_Order_Default
{
   /**
    * Prepare item html
    *
    * This method uses renderer for real product type
    *
    * @return string
    */
   protected function _toHtml()
   {   if ($this->getItem()->getOrderItem()) {
           $item = $this->getItem()->getOrderItem();
       } else {
           $item = $this->getItem();
       }
       if ($productType = $item->getRealProductType()) {
           $renderer = $this->getRenderedBlock()->getItemRenderer($productType);
           $renderer->setItem($this->getItem());
           return $renderer->toHtml();
       }
       return parent::_toHtml();
   }
}
