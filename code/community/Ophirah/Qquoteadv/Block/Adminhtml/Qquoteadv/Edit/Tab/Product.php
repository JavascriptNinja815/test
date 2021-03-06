<?php

class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Edit_Tab_Product extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Set product template to display product information in admin tab
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('qquoteadv/product.phtml');
    }

    /**
     * Get Product information from qquote_product table
     * @return object
     */
    public function getProductData()
    {
        $quoteId = $this->getRequest()->getParam('id');
        $product = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        return $product;
    }

    /**
     * Get product Information
     * @param integer $productId
     * @return object
     */
    public function getProductInfo($productId)
    {
        return Mage::getModel('catalog/product')->load($productId);
    }

    /**
     * Get attribute options array
     * @param object $product
     * @param string $attribute
     * @return array
     */
    public function getOption($product, $attribute)
    {
        $superAttribute = array();
        if ($product->isConfigurable()) {
            $superAttribute = Mage::getModel('qquoteadv/configurable')->getSelectedAttributesInfoText($product, $attribute);
        }

        if ($product->getTypeId() == 'simple') {
            $superAttribute = Mage::helper('qquoteadv')->getSimpleOptions($product, unserialize($attribute));
        }
        return $superAttribute;
    }


    /**
     * Get Product information from qquote_request_item table
     * @return object
     */
    public function getRequestedProductData($id, $quote)
    {
//        $quoteId = $this->getRequest()->getParam('id');  
        $product = Mage::getModel('qquoteadv/requestitem')->getCollection()->setQuote($quote)
            ->addFieldToFilter('quoteadv_product_id', $id);

        $product->getSelect()->order('request_qty asc');
        return $product;
    }

    /**
     * Return quote by quote id
     *
     * @param int $quoteId
     * @return collaction
     */
    public function getQuoteInfo()
    {
        $quoteId = $this->getRequest()->getParam('id');
        return Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
    }

    public function getQuoteShipPrice()
    {
        $shippingPrice = $this->getQuoteInfo()->getShippingPrice();
        //0.00 is also price
        return ($shippingPrice > -1) ? Mage::app()->getStore()->roundPrice($shippingPrice) : null;
    }

    public function isAvaliableShipPrice()
    {
        //0.00 is also price
        return ($this->getQuoteShipPrice() > -1) ? true : false;
    }

    /**
     * Get Quote information from qquote_customer table
     * @return object
     */
    public function getQuoteData($collectTotals = true)
    {
        $quote = array();
        $quoteId = $this->getRequest()->getParam('id');

        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        // Set correct store for the quote
        if(count($quote->getCurrency()) > 1){
            $quote->getStore()->setCurrentCurrency(Mage::getModel('directory/currency')->load($quote->getCurrency()));
        }
        // Collect totals
        if ($collectTotals) {
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        }
        return $quote;
    }

    // DEPRECATED!!
    // USE Mage::getModel('qquoteadv/status')->getOptionArray();
    public function getStatusArray()
    {
        return Mage::helper('qquoteadv')->getStatusArray();
    }

    public function getShippingAddress($customer_id, $format = "html")
    {
        $customer = Mage::getModel('customer/customer')->load($customer_id);
        $address = $customer->getDefaultShippingAddress();

        if (!$address) {
            foreach ($customer->getAddresses() as $address) {
                if ($address) {
                    break;
                }
            }
        }

        if (!$address) {
            return null;
        }

        return $address->format($format);
    }

    public function getCustomerGroupName($customer_id)
    {
        $customer = Mage::getModel('customer/customer')->load($customer_id);
        if ($groupId = $customer->getGroupId()) {
            return Mage::getModel('customer/group')
                ->load($groupId)
                ->getCustomerGroupCode();
        }
    }

    public function getCustomerViewUrl($customer_id)
    {
        return $this->getUrl('adminhtml/customer/edit', array('id' => $customer_id));
    }

    public function getCustomerName($customer_id)
    {
        return Mage::getModel('customer/customer')->load($customer_id)->getName();
    }

    public function getStoreViewInfo($storeId)
    {
        if (!$storeId) {
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        $store = Mage::app()->getStore($storeId);
        $params[] = $store->getWebsite()->getName();
        $params[] = $store->getGroup()->getName();
        $params[] = $store->getName();

        return implode('<br />', $params);
    }

    /**
     * Accept option value and return its formatted view
     *
     * @param mixed $optionValue
     * Method works well with these $optionValue format:
     *      1. String
     *      2. Indexed array e.g. array(val1, val2, ...)
     *      3. Associative array, containing additional option info, including option value, e.g.
     *          array
     *          (
     *              [label] => ...,
     *              [value] => ...,
     *              [print_value] => ...,
     *              [option_id] => ...,
     *              [option_type] => ...,
     *              [custom_view] =>...,
     *          )
     *
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        /* @var $helper Mage_Catalog_Helper_Product_Configuration */
        $helper = Mage::helper('catalog/product_configuration');
        $params = array(
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots" onclick="return false">...</a>'
        );
        return $helper->getFormattedOptionValue($optionValue, $params);
    }

    public function getAdminName($id)
    {
        return Mage::helper('qquoteadv')->getAdminName($id);
    }

    /**
     * Create update button
     *
     * @param
     *
     */
    public function getUpdateTotalButton($status = NULL)
    {

        if ($status == 'disabled') {
            $class = 'disabled';
            $onclick = '';
        } else {
            $class = '';
            $onclick = "$('loading-mask').show();save();";
        }

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setOnclick($onclick);

        $button->setLabel($this->__('Update Totals'));
        $button->setClass($class);

        return $button;
    }

    /**
     * ** DEPRECATED **
     * Create Coupon button
     *
     * @param
     */
    public function getCouponButton()
    {

        $class = '';
        $onclick = "$('loading-mask').show();save();";

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setOnclick($onclick);

        $button->setLabel($this->__('Apply'));
        $button->setClass($class);

        return $button;
    }

    /**
     * ** DEPRECATED **
     * Create FixedPrice button
     *
     * @param
     */
    public function getFixedPriceButton()
    {

        $class = '';
        $onclick = "$('loading-mask').show();save();";

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setOnclick($onclick);

        $button->setLabel($this->__('Recalculate'));
        $button->setClass($class);

        return $button;
    }

    /**
     * Create a save button
     *
     * @param Varien_Object $vars
     * @return boolean
     */
    public function getSaveButton($vars = null)
    {
        if (!$vars instanceof Varien_Object) {
            return false;
        }

        $onclick = (!$vars->getData('onclick')) ? "$('loading-mask').show();save();" : $vars->getData('onclick');

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setOnclick($onclick);

        $button->setLabel($vars->getData('label'));
        $button->setClass($vars->getData('class'));

        return $button;
    }

}
