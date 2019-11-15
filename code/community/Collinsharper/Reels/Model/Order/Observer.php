<?php


class Collinsharper_Reels_Model_Order_Observer
{
    public function save_reel_placed($event)
    {
        mage::log(__METHOD__);

        $order = $event->getEvent()->getOrder();

        $this->_processOrder($order);
        return $this;
    }

    function _processOrder($order)
    {
        try {
            $printQueue = Mage::getModel('chreels/printer');
            foreach($order->getAllItems() as $item) {
                try {
                    if ($options = $item->getData('product_options')) {
                        $optionsArr = unserialize($options);
                        foreach($optionsArr['options'] as $x => $option) {
                            if($option['label'] == 'Reel ID') {
                                $reelId = $option['value'];
                                $reel = Mage::getModel('chreels/reels')->load($reelId);
                                // if its not marked as ordered.. mark it..
                                if(!$reel->getData('is_ordered')) {
                                    $reel->setData('is_ordered', 1);
                                    $reel->save();
                                }
                                $printQueue->queueReel($reel, (int)$item->getData('qty_ordered'), $order->getIncrementId(), $item->getId());
                            }
                        }
                    }
                } catch (Exception $e) {
                    mage::logException($e);
                }
            }

        } catch (Exception $e) {
            mage::logException($e);
        }
    }
}

