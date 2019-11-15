<?php

class Bronto_Product_Helper_Data extends Bronto_Common_Helper_Data
{
    const XML_PATH_ENABLED = 'bronto_product/settings/enabled';
    const XML_PATH_MAGE_CRON = 'bronto_product/settings/mage_cron';
    const XML_PATH_DESCRIPTION = 'bronto_product/settings/description';
    const XML_PATH_CHAR_LIMIT = 'bronto_product/settings/char_limit';

    /**
     * Checks if the module is enabled
     *
     * @param string $default
     * @param int $scopeId
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /**
     * Checks if the module has Magento cron enabled
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function canUseMageCron($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_MAGE_CRON, $scope, $scopeId);
    }

    /**
     * Gets the character truncation limit for the description attr
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getCharLimit($scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_CHAR_LIMIT, $scope, $scopeId);
    }

    /**
     * Gets the description attribute for the product description
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDescriptionAttr($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DESCRIPTION, $scope, $scopeId);
    }

    /**
     * Checks if the module is enabled for any store
     *
     * @return bool
     */
    public function isEnabledForAny()
    {
        $stores = Mage::app()->getStores();
        if (is_array($stores) && count($stores) >= 1) {
            foreach ($stores as $store) {
                if ($this->isEnabled('store', $store->getId())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Gets the friendly name for this module
     *
     * @return string
     */
    public function getName()
    {
        return 'Bronto Product Recommendations';
    }

    /**
     * Collects all products that are in some way 'recommended' based
     * on the defined recommendation.
     *
     * @param Bronto_Product_Model_Recommendation $recommendation
     * @param int $storeId
     * @param mixed $originalItems
     * @return array
     */
    public function collectRecommendations(Bronto_Product_Model_Recommendation $recommendation, $storeId = null, $originalItems = array())
    {
        // Don't operate on an invalid recommendation
        if (!$recommendation) {
            return array();
        }
        return $this->collector($recommendation, $storeId, $originalItems)->collect();
    }

    /**
     * Builds a recommendation collector based on the parameters
     *
     * @param Bronto_Product_Model_Recommendation $recommendation
     * @param int $storeId
     * @param mixed $originalItems
     * @return Bronto_Product_Model_Collect
     */
    public function collector(Bronto_Product_Model_Recommendation $recommendation, $storeId = null, $originalItems = array())
    {
        return Mage::getModel('bronto_product/collect')
            ->setRecommendation($recommendation)
            ->setOriginalHash($this->itemsToProductHash($originalItems))
            ->setStoreId($storeId);
    }

    /**
     * Creates a Product Hash table from an item collection or a product array
     *
     * @param mixed $items
     * @return array
     */
    public function itemsToProductHash($items)
    {
        $hash = array();
        foreach ($items as $item) {
            if (is_numeric($item)) {
                $item = Mage::getModel('catalog/product')->load($item);
            }
            if ($item->getParentItem()) {
                continue;
            }
            if (!$item instanceof Mage_Catalog_Model_Product) {
                if (array_key_exists($item->getProductId(), $hash)) {
                    continue;
                }
                $product = $item->getProduct();
                if (!$product) {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                }
            } else {
                $product = $item;
            }
            //$hash[$product->getId()] = $product;
   	    $helper = Mage::helper('bronto_common/product');
            $hash[$product->getId()] = $helper->getConfigurableProduct($product);
        }
        return $hash;
    }

    /**
     * Gathers the currency setting and options for the store in question
     *
     * @param int $storeId (Optional)
     * @return Tuple array($baseCurrency, $currency, $options)
     */
    public function currencyAndOptions($storeId = null)
    {
        $store = Mage::app()->getStore($storeId);
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currencyCode = $store->getDefaultCurrencyCode();
        $baseCurrency = Mage::getModel('directory/currency')->load($baseCurrencyCode);
        $currency = Mage::getModel('directory/currency')->load($currencyCode);
        $options = array(
            'precision' => 2,
            'display' => Zend_Currency::NO_SYMBOL,
        );
        if ($this->useCurrencySymbol($storeId)) {
            unset($options['display']);
        }
        return array($baseCurrency, $currency, $options);
    }

    /**
     * Convenience method for collecting related products and
     * setting bronto delivery fields for a given cart
     *
     * @param Bronto_Product_Model_Recommendation $recommendation
     * @param mixed $items
     * @param Bronto_Api_Delivery_Row $delivery
     * @param int $storeId
     * @return void
     */
    public function collectAndSetFields($recommendation, $items, $delivery, $storeId)
    {
        $this->setRelatedFields(
            $delivery,
            $this->collectRecommendations($recommendation, $storeId, $items),
            $storeId);
    }

    /**
     * Utility for truncating string to a certain limit, split by words
     *
     * @param string $string
     * @param int $limit
     * @return string
     */
    public function truncateText($string, $limit)
    {
        if (strlen($string) > $limit) {
            $words = preg_split('/\s+/', $string);
            $ret = '';
            $size = 0;
            foreach ($words as $word) {
                $wordlen = strlen($word) + 1;
                if (($wordlen + $size) > $limit - 3) {
                    break;
                }
                $ret .= "$word ";
                $size += $wordlen;
            }
            return $ret . '...';
        }
        return $string;
    }

    /**
     * Creates the fields to process by products and store
     *
     * @param array $productIds
     * @param int $storeId (Optional)
     * @return array
     */
    public function relatedFields($productIds, $storeId = null)
    {
        $fieldsByIndex = array();
        list($base, $currency, $options) = $this->currencyAndOptions($storeId);
        $attr = $this->getDescriptionAttr('store', $storeId);
        $limit = $this->getCharLimit('store', $storeId);
        foreach ($productIds as $key => $productId) {
            $index = $key + 1;

            /** @var Mage_Catalog_Model_Product $relatedProduct */
            $relatedProduct = Mage::getModel('catalog/product')
                ->setStore($storeId)
                ->load($productId);

            $price = $relatedProduct->getPrice();
            // Only convert the price if the current price is different from the display
            if ($base != $currency) {
                $price = $base->convert($price, $currency);
            }
            $imageUrl = $this->getProductImageUrl($relatedProduct);
            $fields = array(
                array(
                    "name" => "relatedName_{$index}",
                    "content" => $relatedProduct->getName(),
                    "type" => "html"
                ),
                array(
                    "name" => "relatedPrice_{$index}",
                    "content" => $currency->formatTxt($price, $options),
                    "type" => 'html',
                ),
                array(
                    "name" => "relatedDescription_{$index}",
                    "content" => $this->truncateText($relatedProduct->getData($attr), $limit),
                    "type" => 'html',
                ),
                array(
                    "name" => "relatedSku_{$index}",
                    "content" => $relatedProduct->getSku(),
                    'type' => 'html',
                ),
                array(
                    "name" => "relatedUrl_{$index}",
                    "content" => Mage::helper('bronto_common/product')->getProductAttribute($relatedProduct, 'url', $storeId),
                    "type" => 'html',
                ),
                array(
                    "name" => "relatedImgUrl_{$index}",
                    "content" => $imageUrl,
                    'type' => 'html'
                )
            );
            $fieldsByIndex[] = $fields;
        }
        return $fieldsByIndex;
    }

    /**
     * Sets an array of products, representing related products
     * in a delivery as relatedXxx_# API fields
     *
     * @param Bronto_Api_Delivery_Row $delivery
     * @param array $productIds
     * @param int $storeId
     * @return void
     */
    public function setRelatedFields($delivery, $productIds, $storeId = null)
    {
        $currentData = $delivery->getData();
        if (empty($currentData['fields'])) {
            $currentData['fields'] = array();
        }
        foreach ($this->relatedFields($productIds, $storeId) as $fields) {
            $currentData['fields'] = array_merge($currentData['fields'], $fields);
        }
        // By passing the setField call on the API is far more efficient
        $delivery->setData($currentData);
    }

    /**
     * Process tag content dynamic data
     *
     * @param Bronto_Product_Model_Recommendation $rec
     * @param int $storeId
     * @return array
     */
    public function processTagContent($rec, $storeId = null)
    {
        $productIds = $this->collectRecommendations($rec, $storeId);
        $fields = $this->relatedFields($productIds, $storeId);
        return $rec->processContent($fields);
    }

    /**
     * Gets an API Content Tag for the product rec
     *
     * @param Bronto_Api_Content_Tag
     * @param Bronto_Product_Model_Recommendation
     * @return Bronto_Api_Content_Tag_Row
     */
    public function getContentTagForRecommendation($tagObject, $rec)
    {
        $filter = array(
            'type' => 'OR',
            'name' => array(
                'value' => $rec->getName(),
                'operator' => 'EqualTo',
            )
        );
        if ($rec->hasTagId()) {
            $filter['id'] = array($rec->getTagId());
        }
        $tags = $tagObject->readAll($filter, false);
        $tag = $tags->current();
        if (count($tags) > 1) {
            Mage::throwException("Unable to find unique tag for {$rec->getName()}.");
        } else if (!$tag) {
            $tag = $tagObject->createRow();
            $tag->name = $rec->getName();
        }
        return $tag;
    }
}
