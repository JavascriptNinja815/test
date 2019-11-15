<?php
/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 * 
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the 
 * provided Sweet Tooth License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time Sweet Tooth spent 
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension. 
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Reports]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once(Mage::getModuleDir('controllers', 'TBT_Reports') . DS . 'AjaxController.php');

/**
 * Feeds Controller
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Adminhtml_FeedController extends TBT_Reports_AjaxController
{
    public function indexAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Get next 50 feed items
     * @return $this
     * @throws Zend_Controller_Response_Exception
     */
    public function updateAction()
    {
        $response = array();
        $lastId = $this->getRequest()->getParam('last_id', 0);

        $this->loadLayout(false);
        $lastTransfer = 0;
        $response['transfers'] = array();
        $nextTransfers = $this->getTransferFeedService()->getNextTransfers($lastId);
        $nextTransfers->load();
        $block = $this->getFeedItemBlock();
        foreach ($nextTransfers as $transfer) {
            $block->setItemObject($transfer);
            $response['transfers'][] = array(
                'id'        =>     $block->getId(),
                'message'   =>     $block->getMessage(),
                'classes'   =>     implode(' ', $block->getClasses()),
                'timestamp' =>     $block->getTimestamp()
            );

            if ($transfer->getId() > $lastTransfer) {
                $lastTransfer = $transfer->getId();
                $response['last_transfer'] = $lastTransfer;
            }
        }

        if (empty($response['last_transfer'])) {
            $response['last_transfer']  = $lastId;
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
        
        return $this;
    }

    /**
     * Get previous 20 feed items
     * @return $this
     * @throws Zend_Controller_Response_Exception
     */
    public function loadPreviousAction()
    {
        $response = array();
        $firstId = $this->getRequest()->getParam('first_id');
        $count = $this->getRequest()->getParam('count', 20);

        if ($firstId) {
            $this->loadLayout(false);
            $response['transfers'] = array();
            $response['first_transfer']  = $firstId;
            $previousTransfers = $this->getTransferFeedService()->getPreviousTransfers($firstId, $count);
            $previousTransfers->load();
            $block = $this->getFeedItemBlock();
            foreach ($previousTransfers as $transfer) {
                $block->setItemObject($transfer);
                $response['transfers'][] = array(
                    'id'        =>     $block->getId(),
                    'message'   =>     $block->getMessage(),
                    'classes'   =>     implode(' ', $block->getClasses()),
                    'timestamp' =>     $block->getTimestamp()
                );

                if ($transfer->getId() < $response['first_transfer']) {
                    $response['first_transfer'] = $transfer->getId();
                }
            }

        } else {
            $response['error'] = "First transfer id not specified.";
            $this->getResponse()->setHttpResponseCode(400);
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));

        return $this;
    }

    /**
     * @return TBT_Reports_Model_Service_Transfer_Feed
     */
    protected function getTransferFeedService()
    {
        return Mage::getModel('tbtreports/service_transfer_feed');
    }

    /**
     * @return TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Transfer
     */
    protected function getFeedItemBlock()
    {
        return $this->getLayout()->getBlock('tbtreports.adminhtml.feed.item.transfer');
    }
}