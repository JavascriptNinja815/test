<?php

/**
 * Grid column renderer for payment method.
 */
class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Payment_Grid_Renderer_PaymentMethod extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders the payment method row using the helper's option array.
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $optionArray = Mage::helper('chimage3d')->getPaymentMethodOptionArray();

        if (!isset($optionArray[$row->getPaymentMethod()])) {
            return 'Unknown';
        }

        return $optionArray[$row->getPaymentMethod()];
    }
}
