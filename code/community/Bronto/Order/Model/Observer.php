<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Observer
{

    const NOTICE_IDENTIFIER = 'bronto_order';

    private $_helper;

    public function __construct()
    {
        /* @var Bronto_Order_Helper_Data $_helper */
        $this->_helper = Mage::helper(self::NOTICE_IDENTIFIER);
    }

    public function setHelper($helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Verify that all requirements are met for this module
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     * @access public
     */
    public function checkBrontoRequirements(Varien_Event_Observer $observer)
    {
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        // Verify Requirements
        if (!$this->_helper->varifyRequirements(self::NOTICE_IDENTIFIER, array('soap', 'openssl'))) {
            return;
        }
    }

    /**
     * Process specified number of items for specified store
     *
     * @param mixed $storeId can be store object or id
     * @param int   $limit   must be greater than 0
     *
     * @return array
     * @access public
     */
    public function processOrdersForStore($storeId, $limit)
    {
        // Define default results
        $result = array('total' => 0, 'success' => 0, 'error' => 0);

        // If limit is false or 0, return
        if (!$limit) {
            $this->_helper->writeDebug('  Limit empty. Skipping...');

            return $result;
        }

        // Get Store object and ID
        $store   = Mage::app()->getStore($storeId);
        $storeId = $store->getId();

        // Log that we have begun importing for this store
        $this->_helper->writeDebug("Starting Order Import process for store: {$store->getName()} ({$storeId})");

        // If module is not enabled for this store, log that fact and return
        if (!$store->getConfig(Bronto_Order_Helper_Data::XML_PATH_ENABLED)) {
            $this->_helper->writeDebug('  Module disabled for this store. Skipping...');

            return $result;
        }

        // Retrieve Store's configured API Token
        $token = $store->getConfig(Bronto_Common_Helper_Data::XML_PATH_API_TOKEN);

        /* @var $api Bronto_Common_Model_Api */
        $api = $this->_helper->getApi($token);

        /* @var $orderObject Bronto_Api_Order */
        $orderObject = $api->getOrderObject();

        // Retrieve order queue rows limited to current limit and filtered
        // Filter out imported, suppressed, other stores, and items without order ids
        $orderRows = Mage::getModel('bronto_order/queue')
            ->getCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->addBrontoHasOrderFilter()
            ->orderByUpdatedAt()
            ->setPageSize($limit)
            ->addStoreFilter($storeId)
            ->getItems();

        // If we didn't get any order queue rows with this pull, log and return
        if (empty($orderRows)) {
            $this->_helper->writeVerboseDebug('  No Orders to process. Skipping...');

            return $result;
        }

        /* @var $productHelper Bronto_Common_Helper_Product */
        $productHelper   = Mage::helper('bronto_common/product');
        $descriptionAttr = $store->getConfig(Bronto_Order_Helper_Data::XML_PATH_DESCRIPTION);
        $basePrefix      = $this->_helper->getPriceAttribute('store', $store->getId());
        $inclTaxes       = $this->_helper->isTaxIncluded('store', $store->getId());
        $inclDiscounts   = $this->_helper->isDiscountIncluded('store', $store->getId());
        $uploadMax       = $this->_helper->getBulkLimit('store', $store->getId());
        $orderCache      = array();

        // Cycle through each order queue row
        foreach ($orderRows as $orderRow/* @var $orderRow Bronto_Order_Model_Queue */) {
            $orderId = $orderRow->getOrderId();
            $quoteId = $orderRow->getQuoteId();

            // Check if the order id is still attached to an order in magento
            if ($order = Mage::getModel('sales/order')->load($orderId)/* @var $order Mage_Sales_Model_Order */) {
                // Log that we are processing the current order
                $this->_helper->writeDebug("  Processing Order ID: {$orderId}");
                $orderCache[] = array('orderId' => $orderId, 'quoteId' => $quoteId, 'storeId' => $storeId);

                /* @var $brontoOrder Bronto_Api_Order_Row */
                $brontoOrder            = $orderObject->createRow();
                $brontoOrder->email     = $order->getCustomerEmail();
                $brontoOrder->id        = $order->getIncrementId();
                $brontoOrder->orderDate = date('c', strtotime($order->getCreatedAt()));

                // If there is a conversion tracking id attached to this order, add it to the row
                if ($tid = $orderRow->getBrontoTid()) {
                    $brontoOrder->tid = $tid;
                }
                $brontoOrderItems = array();

                // If the order has been cancelled, placed on hold, or closed we delete the row
                switch ($order->getState()) {
                    case Mage_Sales_Model_Order::STATE_CANCELED:
                    case Mage_Sales_Model_Order::STATE_HOLDED:
                    case Mage_Sales_Model_Order::STATE_CLOSED:
                        $brontoOrder->delete();
                        $orderRow
                            ->setBrontoImported(Mage::getSingleton('core/date')->gmtDate())
                            ->save();
                        break;

                    default:
                        // Get visible items from order
                        $items = $order->getAllVisibleItems();

                        // Keep product order by using a new array
                        $fullItems = array();

                        // loop through the items. if it's a bundled item, 
                        // replace the parent item with the child items.
                        foreach ($items as $item) {
                            $itemProduct = Mage::getModel('catalog/product')->load($item->getProductId());

                            // Handle product based on product type
                            switch ($itemProduct->getTypeId()) {

                                // Bundled products need child items
                                case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                                    if (count($item->getChildrenItems()) > 0) {
                                        foreach ($item->getChildrenItems() as $childItem) {
                                            if ($childItem->getPrice() != 0) {
                                                $item->setPrice(0);
                                            }
                                            $fullItems[] = $childItem;
                                        }
                                    }
                                    $fullItems[] = $item;

                                    break;

                                // Configurable products just need simple config item
                                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                                    $childItems = $item->getChildrenItems();
                                    if (1 === count($childItems)) {
                                        $childItem = $childItems[0];

                                        // Collect options applicable to the configurable product
                                        $productAttributeOptions = $itemProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($itemProduct);

                                        // Build Selected Options Name
                                        $nameWithOptions = array();
                                        foreach ($productAttributeOptions as $productAttribute) {
                                            $itemValue         = $productHelper->getProductAttribute($childItem->getProductId(), $productAttribute['attribute_code'], $storeId);
                                            $nameWithOptions[] = $productAttribute['label'] . ': ' . $itemValue;
                                        }

                                        // Set parent product name to include selected options
                                        $parentName = $item->getName() . ' [' . implode(', ', $nameWithOptions) . ']';
                                        $item->setName($parentName);
                                    }

                                    $fullItems[] = $item;
                                    break;

                                // Grouped products need parent and child items
                                case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                                    // This condition probably never gets hit, parent grouped items don't show in order
                                    $fullItems[] = $item;
                                    foreach ($item->getChildrenItems() as $child_item) {
                                        $fullItems[] = $child_item;
                                    }
                                    break;

                                // Anything else (namely simples) just get added to array
                                default:
                                    $fullItems[] = $item;
                                    break;
                            }
                        }

                        // Cycle through newly created array of products
                        foreach ($fullItems as $item/* @var $item Mage_Sales_Model_Order_Item */) {
                            // If product has a parent, get that parent product
                            $parent = false;
                            if ($item->getParentItem()) {
                                $parent = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item->getParentItem()->getProductId());
                            }

                            /* @var $product Mage_Catalog_Model_Product */
                            $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item->getProductId());

                            // If there is a parent product, use that to get category ids
                            if ($parent) {
                                $categoryIds = $parent->getCategoryIds();
                            } else {
                                $categoryIds = $product->getCategoryIds();
                            }

                            // If the product type is simple and the description
                            // is empty, then attempt to find a parent product
                            // to backfill the description.
                            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && !$product->getData($descriptionAttr)) {
                                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                                if (isset($parentIds[0])) {
                                    $parentProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($parentIds[0]);
                                    $product->setData($descriptionAttr, $parentProduct->getData($descriptionAttr));
                                }
                            }

                            if (empty($categoryIds)) {
                                if (empty($parentProduct)) {
                                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                                    if (isset($parentIds[0])) {
                                        $parentProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($parentIds[0]);
                                    }
                                }
                                if ($parentProduct) {
                                    $categoryIds = $parentProduct->getCategoryIds();
                                }
                            }

                            // Cycle through category ids to pull category details
                            $categories = array();
                            foreach ($categoryIds as $categoryId) {
                                /* @var $category Mage_Catalog_Model_Category */
                                $category     = Mage::getModel('catalog/category')->load($categoryId);
                                $parent       = $category->getParentCategory();
                                $categories[] = $parent->getUrlKey() ? $parent->getUrlKey() : $parent->formatUrlKey($parent->getName());
                                $categories[] = $category->getUrlKey() ? $category->getUrlKey() : $category->formatUrlKey($category->getName());
                            }

                            // Check to ensure there are no duplicate categories
                            $categories = array_unique($categories);

                            // Write orderItem
                            $brontoOrderItems[] = array(
                                'id'          => $item->getId(),
                                'sku'         => $item->getSku(),
                                'name'        => $item->getName(),
                                'description' => $product->getData($descriptionAttr),
                                'category'    => implode(' ', $categories),
                                'image'       => $this->_helper->getItemImg($item, $product, $storeId),
                                'url'         => $this->_helper->getItemUrl($item, $product, $storeId),
                                'quantity'    => (int)$item->getQtyOrdered(),
                                'price'       => $this->_helper->getItemPrice($item, $basePrefix, $inclTaxes, $inclDiscounts)
                            );
                        }
                        $brontoOrder->products = $brontoOrderItems;
                        $brontoOrder->persist();
                        break;
                }

                // increment total number of items processed
                $result['total']++;

                try {
                    // Mark order as imported
//                    $orderRow->setBrontoImported(Mage::getSingleton('core/date')->gmtDate());
//                    $orderRow->save();

                    // Flush every 10 orders
                    if ($result['total'] % $uploadMax === 0) {
                        $result     = $this->_flushOrders($orderObject, $orderCache, $result);
                        $orderCache = array();
                    }
                } catch (Exception $e) {
                    $this->_helper->writeError($e);

                    // Mark import as *not* imported
                    $orderRow->setBrontoImported(null);
                    $orderRow->save();

                    // increment number of errors
                    $result['error']++;
                }
            }
        }

        // Final flush (for any we miss)
        if (!empty($orderCache)) {
            $result = $this->_flushOrders($orderObject, $orderCache, $result);
        }

        // Log results
        $this->_helper->writeDebug('  Success: ' . $result['success']);
        $this->_helper->writeDebug('  Error:   ' . $result['error']);
        $this->_helper->writeDebug('  Total:   ' . $result['total']);

        return $result;
    }

    /**
     * @param Bronto_Api_Order $orderObject
     * @param array            $orderCache
     * @param array            $result
     *
     * @return array
     * @access protected
     */
    protected function _flushOrders($orderObject, $orderCache, $result)
    {
        // Get delivery results from order object
        $flushResult = $orderObject->flush();
        $flushCount  = count($flushResult);

        // Log Order import flush process starting
        $this->_helper->writeDebug("  Flush resulted in {$flushCount} orders processed");
        $this->_helper->writeVerboseDebug('===== FLUSH =====', 'bronto_order_api.log');
        $this->_helper->writeVerboseDebug(var_export($orderObject->getApi()->getLastRequest(), true), 'bronto_order_api.log');
        $this->_helper->writeVerboseDebug(var_export($orderObject->getApi()->getLastResponse(), true), 'bronto_order_api.log');

        // Cycle through flush results and handle any errors that were returned
        foreach ($flushResult as $i => $flushResultRow) {
            if ($flushResultRow->hasError()) {
                $hasError     = true;
                $errorCode    = $flushResultRow->getErrorCode();
                $errorMessage = $flushResultRow->getErrorMessage();
            } else {
                $hasError     = false;
                $errorCode    = false;
                $errorMessage = false;
            }

            if (isset($orderCache[$i])) {
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getModel('sales/order')->load($orderCache[$i]['orderId']);

                /** @var Mage_Core_Model_Store $store */
                $store = Mage::getModel('core/store')->load($orderCache[$i]['storeId']);

                /** @var Mage_Core_Model_Website $website */
                $website = Mage::getModel('core/website')->load($store->getWebsiteId());

                $storeMessage = "For `{$website->getName()}`:`{$store->getName()}`: ";

                /** @var Bronto_Order_Model_Queue $orderRow */
                $orderRow = Mage::getModel('bronto_order/queue')
                    ->getOrderRow($order->getId(), $order->getQuoteId(), $order->getStoreId());
            } else {
                if ($hasError) {
                    Mage::helper('bronto_order')->writeError("[{$errorCode}] {$errorMessage}");
                    $result['error']++;
                }

                continue;
            }

            if ($hasError) {
                // If error code is 915, try to pull customer email address
                if (915 == $errorCode) {
                    if ($customerEmail = $order->getCustomerEmail()) {
                        $errorMessage = "Invalid Email Address: `{$customerEmail}`";
                    } else {
                        $errorMessage = "Email Address is empty for this order";
                    }
                }

                // Append order id to message to assist troubleshooting
                $errorMessage .= " (Order #: {$order->getIncrementId()})";

                // Log and Display error message
                $this->_helper->writeError("[{$errorCode}] {$storeMessage}{$errorMessage}");

                // Reset Bronto Import status
                $orderRow->setBrontoImported(null)
                    ->setBrontoSuppressed($errorMessage)
                    ->save();

                // Increment number of errors
                $result['error']++;
            } else {
                $orderRow->setBrontoImported(Mage::getSingleton('core/date')->gmtDate());
                $orderRow->save();

                // Increment number of successes
                $result['success']++;
            }
        }

        return $result;
    }

    /**
     * Process Orders for all stores
     *
     * @param bool $brontoCron
     *
     * @return array
     */
    public function processOrders($brontoCron = false)
    {
        // Set default result values
        $result = array(
            'total'   => 0,
            'success' => 0,
            'error'   => 0,
        );

        // Only allow cron to run if isset to use mage cron or is coming from bronto cron
        if (Mage::helper('bronto_order')->canUseMageCron() || $brontoCron) {
            // Get limit value from config
            $limit = $this->_helper->getLimit();

            // Pull array of stores to cycle through
            $stores = Mage::app()->getStores(true);

            // Cycle through stores
            foreach ($stores as $_store) {
                // If limit is spent, don't process
                if ($limit <= 0) {
                    continue;
                }

                // Process Orders for store and collect results
                $storeResult = $this->processOrdersForStore($_store, $limit);

                // Append results to totals
                $result['total'] += $storeResult['total'];
                $result['success'] += $storeResult['success'];
                $result['error'] += $storeResult['error'];

                // Decrement limit by resultant total
                $limit = $limit - $storeResult['total'];
            }
        }

        return $result;
    }

}
