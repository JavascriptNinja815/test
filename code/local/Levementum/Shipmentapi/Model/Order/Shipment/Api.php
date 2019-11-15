<?php
class Levementum_Shipmentapi_Model_Order_Shipment_Api extends Mage_Sales_Model_Order_Shipment_Api
{
    /**
     * Create new shipment for order
     *
     * @param string $orderIncrementId
     * @param array $itemsQty
     * @param string $comment
     * @param booleam $email
     * @param boolean $includeComment
     * @return string
     */
    public function create($orderIncrementId, $Carrier="", $title="",$trackNumber="", $itemsQty = array(), $email = true, $includeComment = false, $comment = null)
    {
// we dont email customers on tracking add
	$email = false;
	$includeComment = false;
 Varien_Profiler::start(__METHOD__ . " :: loadOrder");

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
 Varien_Profiler::stop(__METHOD__ . " :: loadOrder");
        /**
          * Check order existing
          */
        if (!$order->getId()) {
             $this->_fault('order_not_exists');
        }

        /**
         * Check shipment create availability
         */
        if (!$order->canShip()) {
             $this->_fault('data_invalid', Mage::helper('sales')->__('Cannot do shipment for order.'));
        }

         /* @var $shipment Mage_Sales_Model_Order_Shipment */
 Varien_Profiler::start(__METHOD__ . " :: ship");
        $shipment = $order->prepareShipment($itemsQty);
        if ($shipment) {
            $shipment->register();
            $shipment->addComment($comment, $email && $includeComment);
            
            if ($email) {
                $shipment->setEmailSent(true);
            }
            $shipment->getOrder()->setIsInProcess(true);
 Varien_Profiler::stop(__METHOD__ . " :: ship");
            try {
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();
                if ($trackNumber!="")
                {
 Varien_Profiler::start(__METHOD__ . " :: track");

                    $this->addTrack($shipment->getIncrementId(),$Carrier,$title,$trackNumber);
 Varien_Profiler::stop(__METHOD__ . " :: track");
                }
mage::log(__METHOD__ . __LINE__ . " send shipment email " );
//                $shipment->sendEmail($email, ($includeComment ? $comment : ''));
            } catch (Mage_Core_Exception $e) {
                $this->_fault('data_invalid', $e->getMessage());
            }
            return $shipment->getIncrementId();
        }
        return null;
    }    
}
?>
