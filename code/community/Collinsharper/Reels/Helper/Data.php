<?php
/**
 * Collinsharper/Reels/Helper/Data.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Reels Extension Helper Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Reels_Helper_Data extends Mage_Core_Helper_Abstract
{

    function getUsersDeletionList()
    {
        return Mage::helper('chreels/cleaner')->hasPotentialDeletableReels();
    }

    function getActiveCustomerReels()
    {
        $returnReels = array();

        $reels = $this->getCustomerReels(true);
        $scheduledForRemoval = $this->getUsersDeletionList();
        if($scheduledForRemoval && is_array($scheduledForRemoval)) {
            foreach($scheduledForRemoval as $rid) {
                if($reel = $reels->getItemById($rid)) {
                    $reel->setIsScheduledForDelete(true);
                    $returnReels[] = $reel;
                    $reels->removeItemByKey($rid);
                }
            }
        }
        foreach($reels as $reel) {
            $returnReels[] = $reel;
        }
        return $returnReels;
    }

    function getCompletedCustomerReels()
    {
        return $this->getCustomerReels(false);
    }

    function getCustomerReelsById($customerId, $onlyActive = true)
    {
        $reels = mage::getModel('chreels/reels')->getCollection();
        if(!$onlyActive) {
            $reels->addFieldToFilter('status', Collinsharper_Reels_Model_Reels::COMPLETE_STATUS);
        } else {
            $reels->addFieldToFilter('status',  array( 'neq' => Collinsharper_Reels_Model_Reels::COMPLETE_STATUS));
        }
        $reels->addFieldToFilter('customer_id', $customerId);
        $reels->getSelect()->Order('updated_at desc');
        return $reels;
    }

    function getCustomerReelByReelId($reelId, $onlyActive = true)
    {
        $reels = mage::getModel('chreels/reels')->getCollection();
        if(!$onlyActive) {
            $reels->addFieldToFilter('status', Collinsharper_Reels_Model_Reels::COMPLETE_STATUS);
        } else {
            $reels->addFieldToFilter('status',  array( 'neq' => Collinsharper_Reels_Model_Reels::COMPLETE_STATUS));
        }
        $reels->addFieldToFilter('entity_id', $reelId);
        $reels->getSelect()->Order('updated_at desc');
        return $reels;
    }

    function getCustomerReels($onlyActive = true)
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        $reels = mage::getModel('chreels/reels')->getCollection();
        if(!$onlyActive) {
            $reels->addFieldToFilter('status', Collinsharper_Reels_Model_Reels::COMPLETE_STATUS);
        } else {
        $reels->addFieldToFilter('status',  array( 'neq' => Collinsharper_Reels_Model_Reels::COMPLETE_STATUS));
	}
        $reels->addFieldToFilter('customer_id', $customerId);
        $reels->getSelect()->Order('updated_at desc');
        return $reels;
    }

    public function log($_message, $_level = null)
    {
        return $this->debugLog($_message, $_level);
    }

    public function debugLog($_message, $_level = null)
    {
        Mage::log($_message, $_level, "ch_reels.log");
    }
}
