<?php 

class Collinsharper_Inquiry_Model_Source_Cmspages {

    public function toOptionArray()
    {
        return Mage::getModel('cms/page')->getCollection()->toOptionArray();
    }

}
