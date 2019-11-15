<?php

class Collinsharper_BrontoNotification_Model_Emailcron
{
	protected $_helper;
	protected $_model;

	private $unorderedBuckets = array('7' => 'Completed Reel - Never Ordered v1',
					  '14' => 'Completed Reel - Never Ordered v2',
					  '30' => 'Completed Reel - Never Ordered v3',
					  '60' => 'Completed Reel - Never Ordered v4');

	private $incompleteBuckets = array('7' => 'Started Reel - Never Finished v1',
                                           '14' => 'Started Reel - Never Finished v2',
                                           '21' => 'Started Reel - Never Finished v3',
                                           '28' => 'Started Reel - Never Finished v4',
                                           '60' => 'Started Reel - Never Finished v5',
                                           '75' => 'Started Reel - Never Finished v6',
                                           '87' => 'Started Reel - Never Finished v7',
                                           '88' => 'Started Reel - Never Finished v7');

	public function sendEmails()
    	{
		$this->_model = Mage::getModel('brontonotification/emailtargets');
		$this->_helper = new Collinsharper_BrontoNotification_Helper_BrontoClient;

		$this->processNoReels();
		$this->processIncompleteReels();
	}

	private function processNoReels()
	{
		$customers = $this->_model->getNoReels();
		foreach($customers as $customer) {
			$contacts = array();
			$contacts[] = array('email' => $customer['email'], 'status' => 'transactional');
			$this->log($contacts[0]['email'] . ' | ' . 'No Reel in Account');
			$this->_helper->sendBrontoEmail('No Reel in Account', $contacts);
		}
	}

	private function processIncompleteReels()
	{
		$customers = $this->_model->getIncompleteReels();
		//Create contacts arrays for each bucket
                foreach ($this->incompleteBuckets as $k => $v) {
                        ${'incomplete' . $k} = array();
                }

		//Put each customer in the correct bucket
		foreach($customers as $customer) {
                    $customer['age'] = 7;
                    ${'incomplete' . $customer['age']}[] = array('email' => $customer['email'], 'status' => 'transactional');
                }

		//Now send emails for buckets with contents
                foreach($this->incompleteBuckets as $k => $v) {
                        if(!empty(${'incomplete' . $k})) {
                                foreach(${'incomplete' . $k} as $contact) {
                                        $contacts = array();
                                        $contacts[] = $contact;
					$this->log($contacts[0]['email'] . ' | ' . $v);
                                        $this->_helper->sendBrontoEmail($v, $contacts);
                                }
                        }
                }
	}

	private function processUnorderedReels()
	{
		$customers = $this->_model->getUnorderedReels();
		//Create contacts arrays for each bucket
		foreach ($this->unorderedBuckets as $k => $v) {
			${'contacts' . $k} = array();
		}

		//Put each customer in the correct bucket
		foreach($customers as $customer) {
			 ${'contacts' . $customer['age']}[] = array('email' => $customer['email'], 'status' => 'transactional');
		}

		//Now send emails for buckets with contents
		foreach($this->unorderedBuckets as $k => $v) {
			if(!empty(${'contacts' . $k})) {
				foreach(${'contacts' . $k} as $contact) {
					$contacts = array();
					$contacts[] = $contact;
					$this->log($contacts[0]['email'] . ' | ' . $v);
					$this->_helper->sendBrontoEmail($v, $contacts);
				}
			}
		}

	}

	private function log($message)
	{
		file_put_contents(BP . DS . 'var/log/bronto_notification.log', date('Y-m-d') . ' ' . $message . "\n", FILE_APPEND);
	}
}

