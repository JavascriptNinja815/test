<?php
class Image3D_Reels_Model_Observer {

	public function insert_file_queue($observer) {

		mage::log(__METHOD__ . __LINE__ . " deprecated?1?!");

		$items = $observer->getOrder()->getAllVisibleItems();
		$reels = array();
		foreach($items as $item) {
			$options = $item->getProductOptions();
			if(empty($options['options'])) {
				continue;
			}
			foreach ($options['options'] as $option) {
				if($option['label'] == 'Reel ID') {
					$reel_id = $option['value'];

					if($reel_id) {
						$dbReels = Mage::getModel('reels/reel')->getCollection();
						$dbReels->addFieldToFilter('id', $reel_id);

						foreach($dbReels as $dbReel) {
							if(!isset($reels[$dbReel->getReelFile()])) {
								$reels[$dbReel->getReelFile()] = array(
									'order_id' => $observer->getOrder()->getIncrementId(),
									'reel_id' => $dbReel->getId(),
									'file' => $dbReel->getReelFile(),
									'quantity' => (int)$item->getQtyOrdered()
								);
							}
							else {
								$reels[$dbReel->getReelFile()]['quantity'] += (int)$item->getQtyOrdered();
							}
						}
					}
				}
			}
		}
		if(!empty($reels)) {
			foreach($reels as $reel_filename => $reel) {
				$newReel = Mage::getModel('reels/queue');
				$newReel->setData($reel)->save();
			}
		}
	}
}
?>