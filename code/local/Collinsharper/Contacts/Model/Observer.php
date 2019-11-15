<?php 

class Collinsharper_Contacts_Model_Observer {


function log($x) {

mage::log($x, null,'ch_Shippingmess.log');

}
	public function sendShipmentEmail() {

define("CH_SEND_EMAIL",true);

		date_default_timezone_set('America/Los_Angeles');
$this->log(__METHOD__ . __LINE__ . " shipping emials " );

// We do not send after 2 45 in the after noon. 
	$isAfterTwo = date('H') > 14 || (date('H') == 14 && date('i') > 45);
$this->log(__METHOD__ . __LINE__ . " time check " );
	$isInValidDay = date('N') > 5;  //  sat or sun we dont send
$this->log(__METHOD__ . __LINE__ . " date check " );
		if ($isAfterTwo || $isInValidDay) {
$this->log(__METHOD__ . __LINE__ . " no shipping emials  atr this t ime" );
			return false; //idle period

		}

		$shipments = Mage::getModel('sales/order_shipment')->getCollection();

		$shipments->getSelect()->order('created_at DESC');
//WE were limiting the time we were checking shipments which caused unndded delay with TX offset
        $shipments
        ->addAttributeToSelect('created_at')
        ->addAttributeToSelect('entity_id')
//	->addFieldToFilter('entity_id', 16968)
		->addFieldToFilter('email_sent',  array(array('neq' => 2), array( 'null' => true)))
		->addFieldToFilter('created_at',
            array(
                'from'     => strtotime('-5 day', time()),
//                'to'       => time(),
                'datetime' => true)
        )

        ;

        //
		//$shipments->getSelect()->limit(200);

        $this->log(__METHOD__ . __LINE__ . "we have shipments " . $shipments->getSelect()->__toString() );
        $this->log(__METHOD__ . __LINE__ . "we have shipments " . $shipments->count() );

		foreach ($shipments as $shipment) {


			if (count($shipment->getAllTracks()) > 0) {

                    $shipment = Mage::getModel('sales/order_shipment')->load($shipment->getEntityId());
                    $this->log(__METHOD__ . __LINE__ .  " sending");

					$shipment->setAllowEmails(true);
$this->log(__METHOD__ . __LINE__ . " ================================== " );
					$shipment->sendEmail();
$this->log(__METHOD__ . __LINE__ . " ================================== " );
					$shipment->setAllowEmails(false);
$this->log(__METHOD__ . __LINE__ . " ================================== " );
					$shipment->setEmailSent(2)->save();
$this->log(__METHOD__ . __LINE__ . " ================================== " );

			}

		}
		
	}

}
