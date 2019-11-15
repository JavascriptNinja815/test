<?php

/**
 * Grid column renderer for extended value
 */
class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Item_Grid_Renderer_ExtendedValue extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency
{
    /**
     * Calculates extended value and sticks it on the row for the parent (currency renderer) to render.
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $extendedValue = ($row->getSellingPrice() * $row->getQuantity()) - $row->getSumAmountRefunded();
        $row->setExtendedValue($extendedValue);
        return parent::render($row);
    }
}
