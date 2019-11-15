<?php
class Webkul_Facebookwallpost_Block_Facebookwallpost extends Mage_Core_Block_Template{
	
	public function __construct(){
        $date = time ();
		$currentDate = $date;
		$filerealtime = Mage::getStoreConfig("facebookwallpost/parameter/time",Mage::app()->getStore()->getId());
		$accesstoken = Mage::getStoreConfig("facebookwallpost/parameter/accesstoken",Mage::app()->getStore()->getId());
		$wkapiid = Mage::getStoreConfig("facebookwallpost/parameter/appid",Mage::app()->getStore()->getId());
		$wkappsecret = Mage::getStoreConfig("facebookwallpost/parameter/secretkey",Mage::app()->getStore()->getId());
		if($filerealtime < $currentDate){	
			$data = $this->fbcurlfn($accesstoken,$wkapiid,$wkappsecret);	
		}		
		if($filerealtime!=0){	
			$data= $this->fbcurlfn($accesstoken,$wkapiid,$wkappsecret);		
		   if(isset($data->error))  {
				$page= $_SERVER["PHP_SELF"];
				$sec="0";
				header("refresh:".$sec.";url=".$page);
				$fbwalldata = new Mage_Core_Model_Config();
				$fbwalldata ->saveConfig("facebookwallpost/parameter/accesstoken",0, "default", 0);
				$fbwalldata ->saveConfig("facebookwallpost/parameter/time",0, "default", 0);
				$store = Mage::getModel("core/store")->getCollection();		
				foreach($store as $storeid){
					$storeId = $storeid->getStoreId();
					$websiteId = $storeid->getWebsiteId();
					$fbwalldata = new Mage_Core_Model_Config();
					$fbwalldata ->saveConfig("facebookwallpost/parameter/accesstoken", 0, "stores", $storeId);
					$fbwalldata ->saveConfig("facebookwallpost/parameter/time", 0, "stores", $storeId);
				}
			}
		}
    }
	
	public function getfacebookid(){
		return Mage::getStoreConfig("facebookwallpost/parameter/facebookid",Mage::app()->getStore()->getId());
	}
	public function getguestentries(){
		return Mage::getStoreConfig("facebookwallpost/parameter/guestentries",Mage::app()->getStore()->getId());
	}
	public function getcomments(){
		return Mage::getStoreConfig("facebookwallpost/parameter/comments",Mage::app()->getStore()->getId());
	}
	public function getshowinwindow(){
		return Mage::getStoreConfig("facebookwallpost/parameter/showinwindow",Mage::app()->getStore()->getId());
	}
	public function getnumcomments(){
		return Mage::getStoreConfig("facebookwallpost/parameter/numcomments",Mage::app()->getStore()->getId());
	}
	public function getwidth(){
		return Mage::getStoreConfig("facebookwallpost/parameter/width",Mage::app()->getStore()->getId());
	}
	public function getheight(){
		return Mage::getStoreConfig("facebookwallpost/parameter/height",Mage::app()->getStore()->getId());
	}
	public function getmediaimg(){
		return Mage::getStoreConfig("facebookwallpost/parameter/mediaimg",Mage::app()->getStore()->getId());
	}
	public function getaccesstoken(){
		return Mage::getStoreConfig("facebookwallpost/parameter/accesstoken",Mage::app()->getStore()->getId());
	}
	public function getseeMore(){
		return Mage::getStoreConfig("facebookwallpost/parameter/seemore",Mage::app()->getStore()->getId());
	}
	public function getseeLess(){
		return Mage::getStoreConfig("facebookwallpost/parameter/seeless",Mage::app()->getStore()->getId());
	}
	public function getcharLen(){
		return Mage::getStoreConfig("facebookwallpost/parameter/limit",Mage::app()->getStore()->getId());
	}
	public function getcharspeed(){
		return Mage::getStoreConfig("facebookwallpost/parameter/speed",Mage::app()->getStore()->getId());
	}
	public function gettheme(){
		return Mage::getStoreConfig("facebookwallpost/parameter/theme",Mage::app()->getStore()->getId());
	}
	public function getfblike(){
		return Mage::getStoreConfig("facebookwallpost/parameter/fblike",Mage::app()->getStore()->getId());
	}
	public function getfblikebox(){
		return Mage::getStoreConfig("facebookwallpost/parameter/fblikebox",Mage::app()->getStore()->getId());
	}
	public function getshow_faces(){
		return Mage::getStoreConfig("facebookwallpost/parameter/show_faces",Mage::app()->getStore()->getId());
	}
	public function getheadertext(){
		return Mage::getStoreConfig("facebookwallpost/parameter/headertext",Mage::app()->getStore()->getId());
	}
	public function getupperheader(){
		return Mage::getStoreConfig("facebookwallpost/parameter/upperheader",Mage::app()->getStore()->getId());
	}
	public function getfacebooklogo(){
		return Mage::getStoreConfig("facebookwallpost/parameter/facebooklogo",Mage::app()->getStore()->getId());
	}
	public function getgroupurlOpt(){
		return Mage::getStoreConfig("facebookwallpost/parameter/groupurlOpt",Mage::app()->getStore()->getId());
	}
	public function getpageurlOpt(){
		return Mage::getStoreConfig("facebookwallpost/parameter/pageurlOpt",Mage::app()->getStore()->getId());
	}
	public function getshowdate(){
		return Mage::getStoreConfig("facebookwallpost/parameter/showdate",Mage::app()->getStore()->getId());
	}
	public function getgroupurl(){
		return Mage::getStoreConfig("facebookwallpost/parameter/groupurl",Mage::app()->getStore()->getId());
	}
	public function getpageurl(){
		return Mage::getStoreConfig("facebookwallpost/parameter/pageurl",Mage::app()->getStore()->getId());
	}
	public function getwallbackgroungcolor(){
		return Mage::getStoreConfig("facebookwallpost/parameter/wall_backgroung_color",Mage::app()->getStore()->getId());
	}
	public function getwalldatacolor(){
		return Mage::getStoreConfig("facebookwallpost/parameter/wall_data_color",Mage::app()->getStore()->getId());
	}
	public function getwallcommentbgcolor(){
		return Mage::getStoreConfig("facebookwallpost/parameter/wall_comment_bgcolor",Mage::app()->getStore()->getId());
	}
	public function getwallcommentcolor(){
		return Mage::getStoreConfig("facebookwallpost/parameter/wall_comment_color",Mage::app()->getStore()->getId());
	}
	public function getshowavatar(){
		return Mage::getStoreConfig("facebookwallpost/parameter/showavatar",Mage::app()->getStore()->getId());
	}
	public function getshowMoreOption(){
		return Mage::getStoreConfig("facebookwallpost/parameter/showMoreOption",Mage::app()->getStore()->getId());
	}
	
	public function fbcurlfn($token, $wkapiid, $wkappsecret)
	{
	$curl_initiate_fn = curl_init();
	curl_setopt($curl_initiate_fn, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token?client_id='.$wkapiid.'&client_secret='.$wkappsecret.'&grant_type=client_credentials&format=json');
	curl_setopt($curl_initiate_fn, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_initiate_fn, CURLOPT_TIMEOUT, 15);
	curl_setopt($curl_initiate_fn, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl_initiate_fn, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl_initiate_fn, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl_initiate_fn, CURLOPT_SSL_VERIFYPEER, 0);
	$data = new stdClass();
	$data->error = null;
	$longaccesstoken = curl_exec($curl_initiate_fn);
	curl_close($curl_initiate_fn);

	$data = json_decode($longaccesstoken);

	if (isset($data->error)) {
		$fbwalldata = new Mage_Core_Model_Config();
		$fbwalldata->saveConfig('facebookwallpost/parameter/accesstoken', 0, 'default', 0);
		$fbwalldata->saveConfig('facebookwallpost/parameter/time', 0, 'default', 0);
		$store = Mage::getModel('core/store')->getCollection();
		foreach ($store as $storeid) {
		$storeId = $storeid->getStoreId();
		$websiteId = $storeid->getWebsiteId();
		$fbwalldata = new Mage_Core_Model_Config();
		$fbwalldata->saveConfig('facebookwallpost/parameter/accesstoken', 0, 'stores', $storeId);
		$fbwalldata->saveConfig('facebookwallpost/parameter/time', 0, 'stores', $storeId);
		}
	} else {
		$date = time();
		$currentDate = $date;
		$filerealtime = Mage::getStoreConfig('facebookwallpost/parameter/time', Mage::app()->getStore()->getId());
		$expiretime = $filerealtime - 864000;
		if ($filerealtime == 0 || $expiretime <= $currentDate) {
			$stripacceess = explode('=', $longaccesstoken);
			$finalstripacees = explode('&expires=', $stripacceess[1]);
			$accesstokenfinal = $stripacceess[1];
			$explodedate = strtotime(date('m-d-Y', '01-01-2020'));
			$changefiletime = $currentDate + $explodedate - 2000;
			$fbwalldata = new Mage_Core_Model_Config();
			$fbwalldata->saveConfig('facebookwallpost/parameter/accesstoken', $accesstokenfinal, 'default', 0);
			$fbwalldata->saveConfig('facebookwallpost/parameter/time', $changefiletime, 'default', 0);
			$store = Mage::getModel('core/store')->getCollection();
			foreach ($store as $storeid) {
				$storeId = $storeid->getStoreId();
				$websiteId = $storeid->getWebsiteId();
				$fbwalldata = new Mage_Core_Model_Config();
				$fbwalldata->saveConfig('facebookwallpost/parameter/accesstoken', $accesstokenfinal, 'stores', $storeId);
				$fbwalldata->saveConfig('facebookwallpost/parameter/time', $changefiletime, 'stores', $storeId);
			}
		}
	}

	return $data;
	}
}