<?php

class Collinsharper_Image3d_Model_Adminhtml_Customergrid_Observer
{

    public function getReferrerTextList()
    {
        $attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'referrer');
        $options = $attribute->getSource()->getAllOptions(false);
        $return = array();
        foreach($options as $k) {
            $return[$k['value']] = $k['label'];
        }
        return $return;
    }

    public function beforeBlockToHtml(Varien_Event_Observer $observer)
    {
        $grid = $observer->getBlock();

        /**
         * Mage_Adminhtml_Block_Customer_Grid
         */
        $customerTags = $this->getReferrerTextList();
        if ($grid instanceof Mage_Adminhtml_Block_Customer_Grid) {

            $grid->addColumnAfter(
                "referrer",
                array(
                    'header' => Mage::helper('chimage3d')->__('How you hear?'),
                    'index'  => "referrer",
                    'type' => 'options',
                    'options' => $customerTags,
                ),
                'billing_region'
            );
        }
    }

    public function _applyMyFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addFieldToFilter('referrer', $value);
    }

    public function beforeCollectionLoad(Varien_Event_Observer $observer)
    {

        $collection = $observer->getCollection();

        if (!isset($collection)) {
            return;
        }

        if ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection) {
            mage::log(__METHOD__ . __LINE__ );
            $collection->addAttributeToSelect('referrer');
        }
    }


}