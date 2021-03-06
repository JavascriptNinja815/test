<?php

class Bronto_Product_Model_Collect extends Bronto_Product_Model_Collect_Abstract
{
    private $_currentCount = 0;

    /**
     * Creates a method using the given source
     *
     * @param string $source
     * @return Bronto_Product_Model_Collect_Abstract
     */
    protected function _method($source)
    {
        return Mage::getModel("bronto_product/collect_$source");
    }

    /**
     * Invokes the source method for filling the product info
     *
     * @param string $target
     * @param string $source
     * @param $product (Optional)
     * @return void
     */
    protected function _invokeSource($target, $source, $product = null)
    {
        if (!empty($source)) {
            $method = $this->_method($source);
            if ($method) {
                Mage::helper('bronto_product')->writeDebug("Invoking {$source} on collection of {$this->_recommendation->getName()} on store {$this->getStoreId()}");
                $productIds = $method
                    ->setStoreId($this->getStoreId())
                    ->setRecommendation($this->_recommendation)
                    ->setOriginalHash($this->_hash + $this->_products)
                    ->setProduct($product)
                    ->setSource($target)
                    ->setRemainingCount($this->getRemainingCount())
                    ->collect();

                $this->_fillProducts($productIds);
            }
        }
    }

    /**
     * Performs the scan for associated products
     *
     * @return array
     */
    public function collect()
    {
        if (is_null($this->_recommendation)) {
            Mage::throwException('Product Recommendation is required for collecting recommended products');
        }

        foreach ($this->_recommendation->getSources() as $source => $method) {
            if ($this->_recommendation->isProductRelated($source)) {
                if (is_null($this->_hash)) {
                    Mage::helper('bronto_product')->writeInfo('originalHash cannot be null for a product related source. Skipping');
                    continue;
                }
                foreach ($this->_hash as $productId => $product) {
                    if ($this->isReachedMax()) {
                        break;
                    }
                    $this->_invokeSource($source, $method, $product);
                }
            } else {
                $this->_invokeSource($source, $method);
            }
        }
        return array_keys($this->_products);
    }
}
