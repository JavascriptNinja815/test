<?php

class Collinsharper_BrontoNotification_Model_Emailtargets extends Mage_Core_Model_Abstract
{

	const CELEBRATE_SITE_ID = 1;
	private $write;
	private $noreelAges = '15, 21, 28, 42';
	private $incompleteAges = '7, 14, 21, 28, 60, 75, 87, 88';
	private $unorderedAges = '7, 14, 30, 60';

	protected function _construct()
	{
		$this->write = Mage::getSingleton("core/resource")->getConnection("core_write");
	}

	private function _getNoreelsQuery()
	{
		$query = "select e.entity_id, e.updated_at, isnull(r.entity_id) has_no_reel from  customer_entity e left join ch_reels r on r.customer_id = e.entity_id where to_days(e.updated_at) > to_days('2015-10-01') and e.store_id = 1 group by  e.entity_id having has_no_reel = 1";
		$query = "select e.entity_id, e.updated_at, isnull(r.entity_id) has_no_reel from  customer_entity e left join ch_reels r on r.customer_id = e.entity_id where e.store_id = {self::CELEBRATE_SITE_ID} group by  e.entity_id having has_no_reel = 1";
	}

	private function _getCustomerByAgeQuery()
	{
		$query = "SELECT entity_id, email, DATEDIFF(CURDATE(), updated_at) AS age 
			  FROM customer_entity
                          WHERE MOD(DATEDIFF(CURDATE(), updated_at), 7) = 0;"; 

		return $query;
	}

	private function _getIncompleteQuery()
	{
		$query = "SELECT c.entity_id, c.email, DATEDIFF(CURDATE(), MIN(r.updated_at)) AS age 
			  FROM customer_entity c, ch_reels r
			  WHERE r.customer_id = c.entity_id
			  AND r.status != 10
                          AND MOD(DATEDIFF(CURDATE(), r.updated_at), 7) = 0 
			  GROUP BY c.entity_id";

		return $query;
	}

	private function _getUnorderedQuery()
	{
		$query = "SELECT c.entity_id, c.email, DATEDIFF(CURDATE(), MIN(r.updated_at)) AS age
                          FROM customer_entity c, ch_reels r
                          WHERE r.customer_id = c.entity_id
			  AND r.status = 10
                          AND r.is_ordered = 0
                          AND DATEDIFF(CURDATE(), r.updated_at) IN ($this->unorderedAges)
                          GROUP BY c.entity_id";

		return $query;
	}

	public function getNoReels()
	{
		$write = Mage::getSingleton("core/resource")->getConnection("core_write");
		$sql = $this->_getCustomerByAgeQuery();
		$rows = $write->query($sql);
		$temp = array();
                $results = array();
		foreach($rows as $row) {
			//See if we have any reels
			$reels = Mage::getModel('chreels/reels')->getCollection();
        		$reels->addFieldToFilter('customer_id', $row['entity_id']);

			if(!$reels->count()) {
				$temp['email'] = $row['email'];
				$results[] = $temp;
			}
		}
		return $results;
	}

	public function getIncompleteReels()
	{
		$write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $sql = $this->_getIncompleteQuery();
                $rows = $write->query($sql);
		$temp = array();
		$results = array();
		foreach($rows as $row) {
			$temp['email'] = $row['email'];
			$temp['age'] = $row['age'];
			$results[] = $temp;
		}
		return $results;
	}

	public function getUnorderedReels()
	{
		$write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $sql = $this->_getUnorderedQuery();
                $rows = $write->query($sql);
		$temp = array();
		$results = array();

		foreach($rows as $row) {
			$temp['email'] = $row['email'];
			$temp['age'] = $row['age'];
			$results[] = $temp;
		}

		return $results;
	}
}
