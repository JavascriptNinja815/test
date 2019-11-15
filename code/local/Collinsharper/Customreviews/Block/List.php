<?php
class Collinsharper_Customreviews_Block_List extends Mage_Core_Block_Template
{
    /*
     * listReviews - returns reviews list object - hardcoded SKU
     */
    public function listReviews()
    {
        $_sku = 'VIEWER';

        $_catalog = Mage::getModel('catalog/product');
        $_productId = $_catalog->getIdBySku($_sku);
        $reviews = Mage::getModel('review/review')->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addEntityFilter('product', $_productId)
            ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
            ->setDateOrder()
            ->addRateVotes();

        return $reviews ;
     }
    public function listSharedReels()
    {
        $reelsMain = mage::getModel('chreels/reels')->getCollection();
        $reelsMain->addFieldToFilter('is_public',  array( 'eq' => 1 ));

        $reels = array();
        foreach ($reelsMain as $reel) {
            $imgReel = $reel->getData("final_reel_file");
	        $pathDetails = pathinfo($imgReel);
            $imgReelFinalUrl = $pathDetails['dirname'] . "/" . $pathDetails['filename'] . "_shared.jpg";
            $fullFilePath = BP . "/media/" . $imgReelFinalUrl;
            //mage::log($fullFilePath);
            if (file_exists ( $fullFilePath )) {
                $reels[] = "media/" . $imgReelFinalUrl;
            }

        }


        return $reels;
    }
}
