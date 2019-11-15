<?php
class Collinsharper_BrontoNotification_Model_Cron{	

    private function __sendBrontoEmail($message_name, $contacts) {

        //No Reel
        //Reel Not Complete
        //delete reel


        $client = new SoapClient('https://api.bronto.com/v4?wsdl', array('trace' => 1,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS));

        try {

            // Add in a valid API token
            $token = Mage::getStoreConfig(Bronto_Common_Helper_Data::XML_PATH_API_TOKEN);

            print "logging in\n";
            $sessionId = $client->login(array('apiToken' => $token))->return;

            $session_header = new SoapHeader("http://api.bronto.com/v4", 'sessionHeader', array('sessionId' => $sessionId));
            $client->__setSoapHeaders(array($session_header));

            $filter = array('name' => array('operator' => 'EqualTo',
                    'value' => $message_name)
            );

            $message = $client->readMessages(array('pageNumber' => 1,
                        'includeContent' => false,
                        'filter' => $filter)
                    )->return[0];

            if (!$message) {
                print "There was an error retrieving the message ID.\n";
                exit;
            }

            print "Adding contact with the following attributes\n";
            $contact_write_result = $client->addOrUpdateContacts($contacts)->return;
            if ($contact_write_result->errors) {
                print "There was a problem adding or updating the contact:\n";
                print_r($contact_write_result->results);
            } elseif ($contact_write_result->results[0]->isNew == true) {
                print "The contact has been added with a status of 'transactional'.  Contact Id: " . $contact_write_result->results[0]->id . "\n";
            } else {
                print "The contact already exists. You can send transactional emails to them.  Contact Id: " . $contact_write_result->results[0]->id . "\n";
            }

            // Make delivery start timestamp
            $now = date('c');

            $deliveryRecipientObject = array('type' => 'contact',
                'id' => $contact_write_result->results[0]->id);

            // Create an array of delivery parameters including the content
            // which will be displayed by the loop tags added in the example
            // message.
            $delivery = array();
            $delivery['start'] = $now;
            $delivery['messageId'] = $message->id;
            $delivery['fromName'] = Mage::getStoreConfig('trans_email/ident_general/name');
            $delivery['fromEmail'] = Mage::getStoreConfig('trans_email/ident_general/email');
            $delivery['recipients'] = array($deliveryRecipientObject);
            //$delivery['type'] = "transactional";

            // Notice below that when you reference the name of the loop tag via the API,
            // be sure to leave of the "%%# _#%%" portion of the tag. You will build
            // an array using individual API message tags which are named
            // as follows: basename_number. For example, name => item_1, rather
            // than name => %%#item_#%%.
//            $delivery['fields'][] = array('name' => 'productname_1', 'type' => 'html', 'content' => 'A Cool Shirt');
//            $delivery['fields'][] = array('name' => 'productname_2', 'type' => 'html', 'content' => 'Some Nice Shoes');
//            $delivery['fields'][] = array('name' => 'productname_3', 'type' => 'html', 'content' => 'A Trendy Hat');
//            $delivery['fields'][] = array('name' => 'productprice_1', 'type' => 'html', 'content' => '$20.99');
//            $delivery['fields'][] = array('name' => 'productprice_2', 'type' => 'html', 'content' => '$50.99');
//            $delivery['fields'][] = array('name' => 'productprice_3', 'type' => 'html', 'content' => 'FREE!');
            $deliveries[] = $delivery;

            $parameters = array('deliveries' => $deliveries);
            $res = $client->addDeliveries($parameters)->return;

            if ($res->errors) {
                print "There was a problem scheduling your delivery:\n";
                print $res->results[$res->errors[0]]->errorString . "\n";
            } else {
                print "Delivery has been scheduled.  Id: " . $res->results[0]->id . "\n";
            }
        } catch (Exception $e) {
            print "uncaught exception\n";
            print_r($e);
        }
    }


	public function __sendNofification(){

		//-------------------------- NO REEL ----------------------------------

		$collection = Mage::getModel('customer/customer')->getCollection();

		$collection->addAttributeToSelect('bronto_notification_type');

		$collection->getSelect()->joinLeft(
			array('ur' => 'user_reels'),
			'e.entity_id=ur.user_id',
			array()
		);

		$collection->getSelect()->where('e.created_at < (NOW() - INTERVAL '.Mage::getStoreConfig('bronto_reminder/notification/no_action_period').' day)');

		$collection->getSelect()->where('ur.user_id IS NULL');

		$contacts = array();

		foreach ($collection as $customer) {

			$contacts[] = array('email' => $customer->getEmail(), 'status' => 'transactional');

		}

		$this->sendBrontoEmail('No Reel', $contacts);

		//------------------------- REEL NOT COMPLETE -------------------------


                $collection = Mage::getModel('customer/customer')->getCollection();

                $collection->addAttributeToSelect('bronto_notification_type');

                $collection->getSelect()->joinLeft(
                        array('ur' => 'user_reels'),
                        'e.entity_id=ur.user_id',
                        array()
                );

                $collection->getSelect()->joinLeft(
                        array('r' => 'reels'),
                        'ur.reel_id=r.id',
                        array()
                );

		$collection->getSelect()->where('ur.user_id IS NOT NULL');

		$collection->getSelect()->where('r.preview_path=\'\'');

                $contacts = array();

                foreach ($collection as $customer) {

                        $contacts[] = array('email' => $customer->getEmail(), 'status' => 'transactional');

                }

                $this->sendBrontoEmail('Reel Not Complete', $contacts);

		//------------------------ NO ACTION REEL IS ABOUT TO BE DELETED ------

                $collection = Mage::getModel('customer/customer')->getCollection();

                $collection->addAttributeToSelect('bronto_notification_type');

                $collection->getSelect()->joinLeft(
                        array('ur' => 'user_reels'),
                        'e.entity_id=ur.user_id',
                        array()
                );

                $collection->getSelect()->joinLeft(
                        array('r' => 'reels'),
                        'ur.reel_id=r.id',
                        array()
                );

                $collection->getSelect()->where('ur.user_id IS NOT NULL');

                $collection->getSelect()->where('r.updated_at < (NOW() - INTERVAL '.Mage::getStoreConfig('bronto_reminder/notification/no_action_period').' DAY)');

                $contacts = array();

                foreach ($collection as $customer) {

                        $contacts[] = array('email' => $customer->getEmail(), 'status' => 'transactional');

                }

                $this->sendBrontoEmail('delete reel', $contacts);


	} 




}
