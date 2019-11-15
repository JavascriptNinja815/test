<?php
class Collinsharper_Inquiry_Model_Source_Inquirytype
{

    CONST STEREO_INQUIRY = 2;
    CONST ESTIMATE_INQUIRY = 3;
    CONST BLOCK_PREFIX = 'ch_inquiry_';
    CONST ORDER_INQUIRY = 33;

// TODO this should come from some grid where you can configure types of inquiries. ideally we are building like a gtneeric contact form machine thing
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(

            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Stereo')),
            array('value' => 3, 'label'=>Mage::helper('adminhtml')->__('Market Estimate Tool')),
            array('value' => 33, 'label'=>Mage::helper('adminhtml')->__('Market Order Inquiry')),
        );
    }

    public function getBlockId($x)
    {
        $ops = array(
//            2 => self::BLOCK_PREFIX . 'chcategoryforms/type/stereo.phtml',
//            3 => self::BLOCK_PREFIX . 'chcategoryforms/type/b2b_estimate.phtml',
//            33 => self::BLOCK_PREFIX . 'chcategoryforms/type/b2b_order.phtml',
            2 => 'chcategoryforms/type/stereo.phtml',
            3 => 'chcategoryforms/type/b2b_estimate.phtml',
            33 => 'chcategoryforms/type/b2b_order.phtml',
        );
        return isset($ops[$x]) ? $ops[$x] : false;
    }

    public function getOptions()
    {
        $ret = array();
        foreach($this->toOptionArray() as $value) {
            $ret[$value['value']] = $value['label'];

        }
        return $ret;
    }

    function getAddressBlock($orderData, $prefix = '', $addressId = false)
    {
        if($addressId) {
            $_address = new Mage_Customer_Model_Address;
            $_address->load((int) $addressId);
            return $_address->format('html');
        }

        $out = '';
        $street = $orderData->getData($prefix . 'street');
        $streetOne = isset($street[0]) ? $street[0] : '';
        $streetTwo = isset($street[1]) ? $street[1] : '';

        $region = $orderData->getData($prefix . 'region');
        if($orderData->getData($prefix . 'region_id')) {
            $region = Mage::getModel('directory/region')->load($orderData->getData($prefix . 'region_id'));
        } else {
	$region = new Varien_Object();
	$region->setName($orderData->getData($prefix . 'region'));
	}


        $out .= $orderData->getData($prefix . 'firstname') . ' ' . $orderData->getData($prefix . 'lastname') . "<br />";
        $out .= $streetOne . "<br />";
        $out .= $streetTwo . "<br />";
        $out .= $orderData->getData($prefix . 'city') . ', ' . $region->getName() . ' ' . $orderData->getData($prefix . 'postcode') . "<br />";
        $out .= $orderData->getData($prefix . 'country_id') .  "<br />";
        $out .= 'T: ' . $orderData->getData($prefix . 'telephone') .  "<br />";
        $out .= 'F: ' . $orderData->getData($prefix . 'fax') .  "<br />";
        return $out;
    }


    function sendInquiryEmailData($emailData)
    {
        return Mage::getModel('chcontacts/mailer_chform')->send($emailData);
    }

    function buildInquiryEmailData($data, $inquiry = false, $email = false, $name = false)
    {
        if(!is_object($data)) {
            $data = new Varien_Object($data);
        }

        $emailData = new Varien_Object();
        $emailData->name = $name ? $name : $inquiry->getData('name');
        $emailData->email = $email ? $email : $inquiry->getData('email');
        foreach ($data->getData() as $key => $val) {
            $emailData->setData(str_replace(' ','_',strtolower($key)), $val);
        }


        $emailData->customer_id = $data->getData('customer_id');
        $emailData->customer_name = $data->getData('customer_name');

        $emailData->inquiry_id = $inquiry->getId();
        $emailData->order_increment_id =  $inquiry->getIncrementId() ? $inquiry->getIncrementId() : $inquiry->getId();

        return $emailData;
    }

}
