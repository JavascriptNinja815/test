<?php

/**
 * Grid column renderer for reel platform
 *
 * @author Rudie Wang <rwang@collinsharper.com>
 */
class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Item_Grid_Renderer_Platform extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Extracts platform from the platform data
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $platform = $row->getOrderSource();

        if (is_null($platform) || $platform == '') {
            $platform = 'Web';
        }
        $row->setOrderSource($platform);
        return parent::render($row);
    }
}
