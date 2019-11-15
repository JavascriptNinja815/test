<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Ordertags
 * @version    1.3.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

class AW_Ordertags_Model_Observer extends Mage_Core_Block_Abstract
{
    const AW_COLLPUR_CONFIG_PATH = 'collpur/general/enable';

    /**
     * Observing order status changes$orderId
     *
     * @param mixed $observer
     */
    public function orderStatusChanged($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getStatus()) {
            $objectToValidate = $this->_prepareToValidate($order);
            Mage::getModel('ordertags/managetags')->validateObject($objectToValidate, $order);

        }
    }

    /**
     * Trigger "aw_collpur_deal_status_changed" Event
     * Compatibility with Group Deals
     */
    public function dealStatusChanged($observer)
    {
        $orderIds = $this->_getOrdersWithDeals((array)$observer->getDeal()->getId());
        $this->revalidateOrders($orderIds);
    }

    /**
     * @param array $orderIds
     * return void
     */
    public function revalidateOrders($orderIds)
    {
        if (!empty($orderIds)) {
            $relatedOrders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter(
                'entity_id', array('in' => $orderIds)
            );
            foreach ($relatedOrders as $relatedOrder) {
                $objectToValidate = $this->_prepareToValidate($relatedOrder);
                Mage::getModel('ordertags/managetags')->validateObject($objectToValidate, $relatedOrder);
            }
        }
    }

    private function _orderHasDealItems($order)
    {
        $dealIds = array();
        foreach ($order->getAllItems() as $item) {
            $buyRequest = false; // Mage::helper('collpur')->getBuyRequest($item);
            if ($buyRequest && $dealId = $buyRequest->getData('deal_id')) {
                $dealIds[] = $dealId;
            }
        }
        if (!empty($dealIds)) {
            $this->setDealItemIds($dealIds);
            return true;
        }

        return false;
    }

    /**
     * @param array $dealIds
     * @param array|string|boolean $excludeOrders
     *
     * @return array
     *
     */
    private function _getOrdersWithDeals($dealIds, $excludeOrders = false)
    {
        if (!$dealIds) {
            return array();
        }
        $excludeOrders = (array)$excludeOrders;
        $dealIds = (array)$dealIds;

        $purchases = Mage::getModel('collpur/dealpurchases')->getCollection()->addFieldToFilter(
            'deal_id', array('in' => $dealIds)
        );

        if ($excludeOrders) {
            $purchases->addFieldToFilter('order_id', array('nin' => $excludeOrders));
        }

        $orderIds = array();
        foreach ($purchases as $purchase) {
            $orderIds[] = $purchase->getOrderId();
        }

        return array_unique($orderIds);
    }

    public function pageLoadBefore($observer)
    {
        $moduleDisabled = Mage::getStoreConfig('advanced/modules_disable_output/AW_Ordertags');
        if (1 || !$moduleDisabled) {
            $node = Mage::getConfig()->getNode('global/blocks/adminhtml/rewrite');
            $dnode = Mage::getConfig()->getNode('global/blocks/adminhtml/drewrite/sales_order_grid');
            $node->appendChild($dnode);
        }
    }

    private function _prepareToValidate($_order)
    {
        $orderItemTotal = 0;
        $objToValidate = new Varien_Object();
        $orderItems = $_order->getAllItems();

        $skus = array();
        foreach ($orderItems as $item) {

            $orderItemTotal += $item->getQtyInvoiced();

            if ($item->getParentItem()) {
                continue;
            }

            $skus[] = $item->getSku();

            if ($item->getProductType() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                $this->_addConfProductSku($item, $skus);
            }
        }

        if (count($skus)) {
            $objToValidate->setSku($skus);
        }

        if (isset($orderItemTotal)) {
            $objToValidate->setOrderItemsTotal($orderItemTotal);
        }

        /* Import additional data from order */
        $this->_importDataFromOrder($objToValidate, $_order);


        /* Add deals info to object IF group deals extension is installed */
        if (0 && Mage::helper('ordertags')->extensionEnabled('AW_Collpur', self::AW_COLLPUR_CONFIG_PATH)) {
            $this->_addDealsInfoToObject($objToValidate, $orderItems);
        }
        /*****************************************************************/
        $objToValidate->setPaymentMethod($_order->getPayment()->getMethod());

        return $objToValidate;
    }

    private function _importDataFromOrder($obj, $order)
    {
        $obj
            ->setStoreId($order->getStoreId())
            ->setOrderId($order->getEntityId())
            ->setOrderTotal($order->getSubtotal())
            ->setOrderGrandTotal($order->getGrandTotal())
            ->setOrderStatus($order->getStatus())
        ;

        $orderShipingAdress = $order->getShippingAddress();
        $orderBillingAddress = $order->getBillingAddress();

        if ($orderShipingAdress) {
            $obj->setData('shipping_country', $orderShipingAdress->getCountryId());
        }
        if ($orderBillingAddress) {
            $obj->setData('billing_country', $orderBillingAddress->getCountryId());
        }

    }

    private function _addConfProductSku($item, &$skus)
    {
        $confProduct = Mage::getSingleton('catalog/product')->load($item->getProductId());
        if (!$confProduct->getId() || ($confProduct->getId() != $item->getProductId())) {
            return false;
        }

        $sku = $confProduct->getSku();
        if (!empty($sku)) {
            $skus[] = $sku;
            return true;
        }

        return false;
    }

    private function _addDealsInfoToObject($obj, $orderItems)
    {
        $numberOfDealsInOrder = 0;
        $dealsProgress = array();

        foreach ($orderItems as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $buyRequest = Mage::helper('collpur')->getBuyRequest($item);
            $dealId = $buyRequest->getData('deal_id');
            if ($buyRequest && $dealId) {
                $numberOfDealsInOrder++;
            }

            $deal = Mage::getModel('collpur/deal')->load($dealId);

            if ($deal->getId()) {
                if ($deal->getProgress() == AW_Collpur_Model_Source_Progress::PROGRESS_EXPIRED || $deal->isFailed()) {
                    $dealsProgress[] = 'failed';
                } elseif ($deal->getIsSuccess()) {
                    $dealsProgress[] = 'successed';
                } else {
                    $dealsProgress[] = 'pending';
                }
            }
            $dealsProgress = array_unique($dealsProgress);
        }

        $obj->setData('order_contains_deal', $numberOfDealsInOrder);
        $obj->setData('order_deal_status', $dealsProgress);
    }
}
