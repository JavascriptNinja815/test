<?php
class Image3D_Reels_Model_MySql4_Queue extends Mage_Core_Model_Mysql4_Abstract {
	protected function _construct() {
		$this->_init('reels/queue', 'file_id');
	}   
}
?>