<?php
/**
 * Created by JetBrains PhpStorm.
 * User: shaneray
 * Date: 8/18/14
 * Time: 8:24 AM
 * To change this template use File | Settings | File Templates.
 */ 
class Collinsharper_Chcustomeruploads_Helper_Data extends Mage_Core_Helper_Abstract {

 	public function getThumbnailPath($itemId)
	{
		return Mage::getUrl('chuploads/index/thumbnail/', array('id' => $itemId));
	}
}
