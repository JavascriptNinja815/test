<?php
class Image3D_Sales_Block_Renderer_Grid extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
		$reels = array();
		$items = $row->getAllItems();
		if(!empty($items)) {
			foreach($items as $item) {
				$options = unserialize($item->getData('product_options'));
				if(!empty($options) && !empty($options['options'])) {
					foreach($options['options'] as $option) {
						if($option['label'] == 'Reel ID' && !in_array($option['value'], $reels)) {
							$reels[] = $option['value'];
						}
					}
				}
			}
		}

		if(!empty($reels)) {
			return implode(', ', $reels);
		}
    }
}
?>