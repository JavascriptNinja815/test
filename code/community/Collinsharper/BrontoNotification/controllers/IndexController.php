<?php

class Collinsharper_BrontoNotification_IndexController extends Mage_Core_Controller_Front_Action
{
	protected $_helper;
	protected $_model;

	private $unorderedBuckets = array('7' => 'Unordered Week 1',
					  '14' => 'Unordered Week 2',
					  '30' => 'Unordered Month 1',
					  '60' => 'Unordered Month 2');

	private $incompleteBuckets = array('7' => 'Incomplete Week 1',
                                           '14' => 'Incomplete Week 2',
                                           '21' => 'Incomplete Week 3',
                                           '28' => 'Incomplete Week 4',
                                           '60' => 'Incomplete Month 2',
                                           '75' => 'Incomplete 75',
                                           '87' => 'Incomplete 87',
                                           '88' => 'Incomplete 88');

	public function indexAction()
    	{
		$this->_model = Mage::getModel('brontonotification/emailtargets');
		$this->_helper = new Collinsharper_BrontoNotification_Helper_BrontoClient;

		$this->processNoReels();
		$this->processIncompleteReels();
		$this->processUnorderedReels();
	}

	private function processNoReels()
	{
		$customers = $this->_model->getNoReels();
		foreach($customers as $customer) {
			$contacts = array();
			$contacts[] = array('email' => $customer['email'], 'status' => 'transactional');
			$this->log($contacts[0]['email'] . ' | ' . 'No Reel in Account');
			//$this->_helper->sendBrontoEmail('No Reel in Account', $contacts);
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
                         ${'incomplete' . $customer['age']}[] = array('email' => $customer['email'], 'status' => 'transactional');
                }

		//Now send emails for buckets with contents
                foreach($this->incompleteBuckets as $k => $v) {
                        if(!empty(${'incomplete' . $k})) {
                                foreach(${'incomplete' . $k} as $contact) {
                                        $contacts = array();
                                        $contacts[] = $contact;
					$this->log($contacts[0]['email'] . ' | ' . $v);
                                        //$this->_helper->sendBrontoEmail($v, $contacts);
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
					//$this->_helper->sendBrontoEmail($v, $contacts);
				}
			}
		}

	}

	private function log($message)
        {
                file_put_contents(BP . DS . 'var/log/bronto_debug.log', date('Y-m-d') . ' ' . $message . "\n", FILE_APPEND);
        }
}

