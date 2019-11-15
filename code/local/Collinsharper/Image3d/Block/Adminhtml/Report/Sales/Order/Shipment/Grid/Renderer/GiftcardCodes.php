<?php

/**
 * Grid column renderer for giftcard codes
 */
class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Shipment_Grid_Renderer_GiftcardCodes extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Extracts the giftcard codes from the serialized data
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
	$arrGiftcards = unserialize($row->getGiftCards());
	$strCodes = '';

	if(!empty($arrGiftcards)) {
		foreach($arrGiftcards as $item) {
			$strCodes .= $item['c'] . ' ';
		}
	}
		
	$row->setGiftCards($strCodes);
        return parent::render($row);
    }
}

