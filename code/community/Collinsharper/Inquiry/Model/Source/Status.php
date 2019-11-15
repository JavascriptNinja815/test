<?php
class Collinsharper_Inquiry_Model_Source_Status
{

CONST STATE_NEW = 1;
CONST STATE_VIEWED = 2;
CONST STATE_ARCHIVED = 3;
CONST STATE_DELETED = 9;

// TODO this should come from some grid where you can configure types of inquiries. ideally we are building like a gtneeric contact form machine thing
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(

// todo use the local constants 
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('New')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Viewed')),
            array('value' => 3, 'label'=>Mage::helper('adminhtml')->__('Archived')),
            array('value' => 4, 'label'=>Mage::helper('adminhtml')->__('Responded')),
            array('value' => 5, 'label'=>Mage::helper('adminhtml')->__('Ordered')),
            array('value' => 6, 'label'=>Mage::helper('adminhtml')->__('Pending')),
            array('value' => 9, 'label'=>Mage::helper('adminhtml')->__('Deleted')),
        );
    }

    public function getOptions()
    {
        $ret = array();
        foreach($this->toOptionArray() as $value) {
            $ret[$value['value']] = $value['label'];

        }
        return $ret;
    }

}
