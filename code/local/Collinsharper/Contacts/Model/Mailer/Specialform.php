<?php

class Collinsharper_Contacts_Model_Mailer_Specialform extends Collinsharper_Contacts_Model_Mailer
{
    const STORE_EMAIL_TEMPLATE_ID = 'contacts/specialcontact/specialformstore_template';
    const CUSTOMER_EMAIL_TEMPLATE_ID = 'contacts/specialcontact/specialformcustomer_template';
    const CRM_WEBSERVICE_URL = 'http://sf96.soffront.com/SFRestApi/CreateWebform/U1FMVFJBQ0tTRVJWRVJ8SU1BR0UzRHxUcmFja1dlYlRpY2tldHN8VHJhY2tXZWJUaWNrZXRz/Leads';

    public function send($dataObj, $files=array())
    {
        $result = false;

        try {
            // Save the data into the CRM
            $this->postCRM($dataObj);

mage::log(__METHOD__ . __LINE__ . " shane " . print_r($dataObj,1));
            // Send email to Store first
            $this->sendEmail($dataObj, Mage::getStoreConfig(self::STORE_EMAIL_TEMPLATE_ID), $files);

            // Send email to Customer last
            mage::loG(__METHOD__ . " shjane " . print_r($dataObj, 1));
mage::log(__METHOD__ . __LINE__ );
            
            //$this->sendEmail($dataObj, Mage::getStoreConfig(self::CUSTOMER_EMAIL_TEMPLATE_ID), $files, $dataObj["Email"], $dataObj["Name"]);
            $this->sendEmail($dataObj, Mage::getStoreConfig(self::CUSTOMER_EMAIL_TEMPLATE_ID), $files, $dataObj->email, $dataObj->name);

mage::log(__METHOD__ . __LINE__ );
            $result = true;
        } catch (Exception $error) {
mage::log(__METHOD__ . __LINE__ . " exception " . $error->getMessage());
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($error->getMessage(), true), null, "contactForm.log");
        }

        return $result;
    }

    private function postCRM($dataObj) {
        $ch = null;
        $fields_string = "";
        $result = false;

        try {
            //set POST variables
            $fields = array(
                'FullName' => urlencode($dataObj->getData("name")),
                'Campaign' => urlencode($dataObj->getData("Campaign")),
                'Company' => urlencode($dataObj->getData("company_name")),
                'Address1' => urlencode($dataObj->getData("address")),
                'City' => urlencode($dataObj->getData("city")),
                'AccState' => urlencode($dataObj->getData("state")),
                'ZipCode' => urlencode($dataObj->getData("zip")),
                'Email' => urlencode($dataObj->getData("email")),
                'MainPh' => urlencode($dataObj->getData("phone"))
            );

            foreach($fields as $key=>$value) {
                $fields_string .= $key.'='.$value.'&';
            }

            $fields_string = rtrim($fields_string, '&');

            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." INFO: ".print_r($fields_string, true), null, "contactForm.log");

            $ch = curl_init();

            curl_setopt($ch,CURLOPT_URL, self::CRM_WEBSERVICE_URL);
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." INFO: ".print_r($response, true), null, "contactForm.log");

            $result = true;
        } catch (Exception $error) {
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($error->getMessage(), true), null, "contactForm.log");
        }

        if ($ch != null) {
            curl_close($ch);
        }

        return $result;
    }
}
