<?php
class Image3D_Reels_Helper_Reels extends Mage_Core_Helper_Abstract {
	function loadReels($reel_id = 0, $only_complete = true, $only_incomplete = false, $show_hidden = true) {
		$admin = Mage::getSingleton('admin/session');
		$adminLoggedIn = $admin->isLoggedIn();

		$userID = Mage::getSingleton('customer/session')->getCustomer()->getID();
		if(!$userID && !$adminLoggedIn) {
			return array();
		}

		$dbReels = Mage::getModel('reels/reel')->getCollection();
		if($reel_id) {
			$dbReels->addFieldToFilter('id', $reel_id);
		}
		if($only_complete) {
			$dbReels->addFieldToFilter('preview_path', array('neq' => ''));
		}
		if($only_incomplete) {
			$dbReels->addFieldToFilter('preview_path', '');
		}
		if(!$show_hidden) {
			$dbReels->addFieldToFilter('hidden', 0);
		}
		
		$dbReels->getSelect()->join(
			array('user_reels' => 'user_reels'), 
			'main_table.id = user_reels.reel_id', 
			''
		,'image3d_live'
		);

		if(!$adminLoggedIn) {
			$dbReels->addFieldToFilter('user_reels.user_id', $userID);
		}
		
		$reels = array();
		
		foreach($dbReels as $dbReel) {
			$reels[] = $dbReel;
		}
		
mage::log(__METHOD__ . __LINE__ . "w ehave reel count " . count($reels));
		return $reels;
	}
	
	function image3DIncludes() {
		global $db;

		define('SCRIPT_ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/'));
		require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/classes/Portfolio.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/classes/Reel.php');

		$db = new db();               // INIT DATABASE

$config  = Mage::getConfig()->getResourceConnectionConfig("default_setup");

/*
$dbinfo = array(“host” => $config->host,
            “user” => $config->username,
            “pass” => $config->password,
            “dbname” => $config->dbname
);
*/
		$db->set_user((string)$config->username);  // DATABASE USER
		$db->set_db((string)$config->dbname);    // DATABASE NAME
		$db->set_pax((string) $config->password ); // DATABASE PASSWORD
		$db->set_host((string) $config->host);   // DATABASE HOST (was 173.192.188.62)
		
		return $db;
	}
	
	function deliveryStr($shipping_code) {
		$delivery_str = '';
		
		switch($shipping_code) {
			case 'fedex_GROUNDHOMEDELIVERY':
				$delivery_str = '1-6 business days';
				break;
			case 'fedex_FEDEXEXPRESSSAVER':
				$delivery_str = '3 business days';
				break;
			case 'fedex_FEDEX2DAY':
				$delivery_str = '2 business days';
				break;
			case 'fedex_PRIORITYOVERNIGHT':
				$delivery_str = '1 business day (by 10:30am)';
				break;
			case 'fedex_STANDARDOVERNIGHT':
				$delivery_str = '1 business day (by 3:00pm)';
				break;

			case 'usps_Priority Mail':
				$delivery_str = '2-3 business days';
				break;
			case 'usps_First-Class Mail':
				$delivery_str = '2-3 business days';
				break;
			case 'usps_Priority Mail International':
				$delivery_str = '6-10 business days';
				break;
		}
		
		if($delivery_str) {
			return ' (' . $delivery_str . ')';
		}
		else {
			return $delivery_str;
		}
	}
}
?>
