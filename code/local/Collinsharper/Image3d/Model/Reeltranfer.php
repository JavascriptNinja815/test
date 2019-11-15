<?php


class Collinsharper_Image3d_Model_Reeltransfer extends Collinsharper_Image3d_Model_Abstract
{

    public function sendReel($x)
    {

        /*
         * currently the trnasfer system transfers by time.
         * it would be better to add a bit on the order to indicate sent or not ( indexed).
         * as well as a button on the order to rePrint Reels
         */
    }

    public function sendReels()
    {
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->addFieldToFilter('reels_printed', 0);

    }

}