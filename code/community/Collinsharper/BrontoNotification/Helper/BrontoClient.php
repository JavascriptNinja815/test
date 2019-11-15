<?php
class Collinsharper_BrontoNotification_Helper_BrontoClient{	

    public function sendBrontoEmail($message_name, $contacts) {

        //$client = new SoapClient('https://api.bronto.com/v4?wsdl', array('trace' => 1,
         //   'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
	$url = "https://api.bronto.com/v4";
        $client = new SoapClient('https://api.bronto.com/v4?wsdl', array('trace' => 1, 'encoding' => 'UTF-8'));
	$client->__setLocation($url);

        try {

            // Add in a valid API token
            $token = Mage::getStoreConfig(Bronto_Common_Helper_Data::XML_PATH_API_TOKEN);

            print "logging in\n";
            $sessionId = $client->login(array('apiToken' => $token))->return;

            $session_header = new SoapHeader("http://api.bronto.com/v4", 'sessionHeader', array('sessionId' => $sessionId));
            $client->__setSoapHeaders(array($session_header));

            $filter = array(
		'name' => array(
			'operator' => 'EqualTo',
                    	'value' => $message_name,
		)
            );

            $message = $client->readMessages(array('pageNumber' => 1,
                        'includeContent' => false,
                        'filter' => $filter)
                    )->return;

            if (!$message) {
                print "There was an error retrieving the message ID.\n";
                exit;
            }

            print "Adding contact with the following attributes\n";
            $contact_write_result = $client->addOrUpdateContacts($contacts)->return;

            if ($contact_write_result->results->isError) {
                print "There was a problem adding or updating the contact:\n";
                print_r($contact_write_result->results);
            } elseif ($contact_write_result->results->isNew == true) {
                print "The contact has been added with a status of 'transactional'.  Contact Id: " . $contact_write_result->results->id . "\n";
            } else {
                print "The contact already exists. You can send transactional emails to them.  Contact Id: " . $contact_write_result->results->id . "\n";
            }

            // Make delivery start timestamp
            $now = date('c');

            $deliveryRecipientObject = array('type' => 'contact',
                'id' => $contact_write_result->results->id);

            // Create an array of delivery parameters including the content
            // which will be displayed by the loop tags added in the example
            // message.
            $delivery = array();
            $delivery['start'] = $now;
            $delivery['messageId'] = $message->id;
            $delivery['fromName'] = Mage::getStoreConfig('trans_email/ident_general/name');
            $delivery['fromEmail'] = Mage::getStoreConfig('trans_email/ident_general/email');
            $delivery['recipients'] = array($deliveryRecipientObject);
            $delivery['type'] = "transactional";

            $deliveries[] = $delivery;

            $parameters = array('deliveries' => $deliveries);
            $res = $client->addDeliveries($parameters)->return;
var_dump($res);

            if ($res->results->isError) {
                print "There was a problem scheduling your delivery:\n";
                print $res->results[$res->errors[0]]->errorString . "\n";
            } else {
                print "Delivery has been scheduled.  Id: " . $res->results->id . "\n";
            }
        } catch (Exception $e) {
            print "uncaught exception\n";
            print_r($e);
        }
    }
}
