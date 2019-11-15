<?php

class Collinsharper_Chcategoryforms_Helper_Data extends Mage_Core_Helper_Abstract {


    protected $_product;
    protected $_options;

    public function getFormProductData($product)
    {
        $returnData = null;
        $categoryId = $product->getData('category_form_control');

        // need to pretend admin..
        $currentStore = Mage::app()->getStore()->getId();

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $category = Mage::GetModel('catalog/category')->setStoreId(0);
        $category->load($categoryId);

     //   mage::log(__METHOD__ . __LINE__ . " we are missing it here as its not visible? ({$categoryId})" . $category->getName());
     // this seems to not work due to store visibility
        //if($category->getId() && $category->getCategoryForm()) {
        if($category->getId()) {
            // build the json data here
            $returnData = array();
            $jsonData = unserialize($category->getData('category_form_data'));



            $returnData['core_data'] = array(
                'attributes' => array(),
                'orders' => array(),
            );

            if(Mage::getSingleton('customer/session')->getCustomer() && Mage::getSingleton('customer/session')->getCustomer()->getId()) {

                    //mage::log(__METHOD__ . __LINE__ . " cid " . Mage::getSingleton('customer/session')->getCustomer()->getId());

                    $orders = Mage::getResourceModel('sales/order_collection')
                        ->addFieldToSelect('*')
                        ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
                        ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
                        ->setOrder('created_at', 'desc')
                    ;

                $tmpData = array();
                foreach($orders as $order) {
                    $tmpData[$order->getId()] = $order->getIncrementId() . ' : ' . $order->getGrantTotal();
                }
                 $returnData['core_data']['orders'] = $tmpData;
            }

            $colors = array();
            $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'imprint_color');
            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);
                foreach($options as $opt) {
                    $colors[strtolower(preg_replace('/[^a-z0-9]+/i','_',$opt['label']))] = $opt['label'];
                }
            }

            $returnData['core_data']['attributes']['imprint_color']  = $colors;

            $colors = array();
            $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'viewer_color');
            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);
                foreach($options as $opt) {
                    $colors[strtolower(preg_replace('/[^a-z0-9]+/i','_',$opt['label']))] = $opt['label'];
                }
            }

            $returnData['core_data']['attributes']['viewer_color']  = $colors;

            //mage::log(__METHOD__ . __LINE__ . " wehave " . print_r($returnData['core_data']['attributes'],1));

            $returnData['initial'] = array(
                'step_name' => $category->getName(),
                'step_description' => $category->getDescription(),

//                $returnData['initial'] = array(
//                    'step_name' => $category->getName(),
//                    'step_description' => $category->getDescription(),
                    'step_js' => isset($jsonData['form_step_js']) ? $jsonData['form_step_js'] : false,
                    'step_buttons' => isset($jsonData['form_button_code']) ? $jsonData['form_button_code'] : false,
                );


            $returnData['steps'] = array();
            // all categories under a form category are valid/
            $children = Mage::getModel('catalog/category')->getCollection()->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
            $children->addAttributeToSelect('*')
                ->addAttributeToFilter('parent_id', $category->getId())
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToSort('position');

            foreach($children as $cat) {
                $jsonData = trim($cat->getData('category_form_data'));

                $jsonData = strlen($jsonData) > 1 ? unserialize($cat->getData('category_form_data')) : false;

                if(!unserialize($cat->getData('category_form_data'))) {
                    mage::log(__METHOD__ . __LINE__ . " wehave " . $cat->getId());
                }


                $currentOptionList = explode(",", $cat->getData('category_form_bundle_options'));
                $currentCustomOptionList = explode(",",  isset($jsonData['form_product_options']) ? $jsonData['form_product_options'] : '');

           //     mage::log(__METHOD__ . __LINE__ . " category ddata " . print_r($jsonData,1));
           //     mage::log(__METHOD__ . __LINE__ . " category form_bundle_options " . print_r($currentOptionList,1));
                if(isset($jsonData['form_step_unique_id']) && 'step_estimate_total' == $jsonData['form_step_unique_id']) {
                  //  mage::log(__METHOD__ . __LINE__ . " wehave " . print_r($jsonData,1));
                //    mage::log(__METHOD__ . __LINE__ . " wehave " . print_r($cat->getData('category_form_data'),1));
                }

                $returnData['steps'][] = array (
                    'step_name' => $cat->getName(),
                    'step_id' =>  isset($jsonData['form_step_unique_id']) ? $jsonData['form_step_unique_id'] : false,
                    'step_description' => $cat->getDescription(),
                    'step_options' => $this->getBundleOptionTitles($product, $currentOptionList),
                    'step_custom_options' => $this->getCustomOptionTitles($product, $currentCustomOptionList),
                    'step_js' => isset($jsonData['form_step_js']) ? $jsonData['form_step_js'] : false,
                    'step_buttons' => isset($jsonData['form_button_code']) ? $jsonData['form_button_code'] : false,

                );
            }
        }

        Mage::app()->setCurrentStore($currentStore);

        //return unserialize($returnData);
        return $returnData;
    }

    public function getBundleOptionTitles($product, $optionList)
    {
        //mage::log(__METHOD__ . __LINE__ . " wehave " . print_r($optionList,1));

        $this->setProduct($product);

        $returnList = array();
        //TODO: we depend heavily on the default title. this is not multi language friendly
        foreach($this->getOptions() as $option) {
            if(in_array($option->getOptionId(), $optionList)) {
                $returnList[$option->getOptionId()] = $option->getDefaultTitle();
            }
        }
        return $returnList;
    }

    public function getCustomOptionTitles($product, $optionList)
    {
        //mage::log(__METHOD__ . __LINE__ . " wehave " . print_r($optionList,1));

        $returnList = array();

        if($product->getOptions()) {

            //TODO: we depend heavily on the default title. this is not multi language friendly
            foreach($product->getOptions() as $option) {
                if(in_array($option->getId(), $optionList)) {
                    $returnList[$option->getId()] = $option->getDefaultTitle();
                }
            }

        }
        return $returnList;
    }

    public function getProduct()
    {
        return $this->_product;
    }

    public function setProduct($product)
    {
        return $this->_product = $product;
    }

    public function getOptions($product = false)
    {
        if($product) {
            $this->_options = false;
        } else {
            $product = $this->getProduct();
        }

        if ($product->getId() && !$this->_options) {
            $product->getTypeInstance(true)->setStoreFilter($product->getStoreId(),
                $product);

            $optionCollection = $product->getTypeInstance(true)->getOptionsCollection($product);

            $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );

            $this->_options = $optionCollection->appendSelections($selectionCollection);
//            if (0 && $this->getCanReadPrice() === false) {
//                foreach ($this->_options as $option) {
//                    if ($option->getSelections()) {
//                        foreach ($option->getSelections() as $selection) {
//                            $selection->setCanReadPrice($this->getCanReadPrice());
//                            $selection->setCanEditPrice($this->getCanEditPrice());
//                        }
//                    }
//                }
//            }
        }
        return $this->_options;
    }

    public function getKeyedOptionArray($options)
    {
        $newOptions = array();
        foreach($options as $option) {
            $id = $option->getOptionId()? $option->getOptionId() : $option->getId();
            $newOptions[$id] = $option;
        }
        return $newOptions;
    }

    public function setOrderTag($data, $orderId)
    {
	$x = Mage::getResourceModel('ordertags/orderidtotagid');
	if(!$x || !is_object($x)) {
		return;
	}
        if (isset($data['tag_select'])) {
            $arrayForDB = $data['tag_select'];
            $arrayFromDB = Mage::getResourceModel('ordertags/orderidtotagid')->getArrayByOrderId($orderId);
            $elementsToAddIntoDB = array_diff($arrayForDB, $arrayFromDB);
            $elementsToRemoveFromDB = array_diff($arrayFromDB, $arrayForDB);
            Mage::getResourceModel('ordertags/orderidtotagid')->addIntoDB($orderId, $elementsToAddIntoDB);
        } else {
            $elementsToRemoveFromDB = "*";
        }

        Mage::getResourceModel('ordertags/orderidtotagid')->removeFromDB($orderId, $elementsToRemoveFromDB);
    }
}
