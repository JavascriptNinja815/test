<?php

class Fooman_Surcharge_Helper_Tax extends Mage_Tax_Helper_Data
{
    /**
     * Magento disconnects applied taxes and sales_order_tax_item
     * re-add surcharge tax here
     *
     * @param $source
     */
    public function getCalculatedTaxes($source)
    {
        $taxesItemsOnly = parent::getCalculatedTaxes($source);
        if ($source->getFoomanSurchargeTaxAmount() != 0) {
            if ($source instanceof Mage_Sales_Model_Order) {
                $order = $source;
            } else {
                $order = $source->getOrder();
            }
            $rates = Mage::getModel('tax/sales_order_tax')->getCollection()->loadByOrder($order)->toArray();
            $allTaxes = Mage::getSingleton('tax/calculation')->reproduceProcess($rates['items']);
            $returnArray = array();
            foreach ($allTaxes as $tax) {
                $returnArray[] = array(
                    'tax_amount'      => $tax['amount'],
                    'base_tax_amount' => $tax['base_amount'],
                    'title'           => $tax['id'],
                    'percent'         => $tax['percent']
                );
            }
        }


        return $taxesItemsOnly;
    }
}