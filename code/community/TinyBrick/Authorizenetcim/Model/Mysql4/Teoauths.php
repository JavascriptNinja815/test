<?php
class TinyBrick_Authorizenetcim_Model_Mysql4_Teoauths extends Mage_Core_Model_Mysql4_Abstract
{
	
	public function _construct()
	{
		$this->_init('authorizenetcim/teoauths', 'authorization_id');
	}
}