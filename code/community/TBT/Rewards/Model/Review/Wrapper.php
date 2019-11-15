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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Review
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Review_Wrapper extends Varien_Object
{

    /**
     * @var Mage_Review_Model_Review
     */
    protected $_review;
    
    /**
     * Flag for sending review approval email
     * 
     * @var bool
     */
    protected $shouldSendReviewApprovalConfirmationEmail;

    /**
     * @param Mage_Review_Model_Review $review
     */
    public function wrap(Mage_Review_Model_Review $review)
    {
        $this->_review = $review;

        return $this;
    }

    /**
     * Return the wrapped review
     * @return Mage_Review_Model_Review
     */
    public function getReview()
    {
        return $this->_review;
    }

    /**
     * Returns true if it's Pending!
     * @return boolean
     */
    public function isPending()
    {
        return $this->getReview()->getStatusId() == Mage_Review_Model_Review::STATUS_PENDING;
    }

    /**
     * Returns true if it's Approved!
     * @return boolean
     */
    public function isApproved()
    {
        return $this->getReview()->getStatusId() == Mage_Review_Model_Review::STATUS_APPROVED;
    }

    /**
     * Returns true if it's not Approved!
     * @return boolean
     */
    public function isNotApproved()
    {
        return $this->getReview()->getStatusId() == Mage_Review_Model_Review::STATUS_NOT_APPROVED;
    }

    /**
     * Approves all associated transfers with a pending status.
     */
    public function approvePendingTransfers()
    {
        $pointsEarned = 0;
        $pointsBeforeReviews = $this->getPointsBalance();
        
        foreach ($this->getAssociatedTransfers() as $transfer) {
            if ($transfer->getStatusId() == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT) {
                //Move the transfer status from pending to approved, and save it!
                $transfer->setStatusId(
                    TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT,
                    TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED
                );

                $transfer->save();
                $pointsEarned += $transfer->getQuantity();
                
                $this->shouldSendReviewApprovalConfirmationEmail = true;
            } 
        }
        
        $this->sendReviewApprovalConfirmationEmail($pointsBeforeReviews, $pointsEarned);
        return $this;
    }
    
    /**
     * Get the customer's points balance
     */
    public function getPointsBalance() 
    {
        $review = $this->getReview();
        $customer = Mage::getModel('rewards/customer')->load($review->getCustomerId());
        $usablePoints = $customer->getUsablePoints();
        
        return $usablePoints[1];
    }

    /**
     * Discards all associated transfers with a pending status.
     */
    public function discardPendingTransfers()
    {
        foreach ($this->getAssociatedTransfers() as $transfer) {
            if ($transfer->getStatusId() == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING) {
                //Move the transfer status from pending to approved, and save it!
                $transfer->setStatusId(
                    TBT_Rewards_Model_Transfer_Status::STATUS_PENDING,
                    TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED
                );
                $transfer->save();
            }
            $this->createTransferForNextReview($transfer->getCustomerId());

        }

        return $this;
    }

    /**
     * Loops through each Special rule. If it applies, create a new pending transfer.
     */
    public function ifNewReview()
    {
        if (Mage::helper('rewards')->getIsAdmin()) {
            return $this;
        }

        $ruleCollection = Mage::getSingleton('rewards/review_validator')->getApplicableRulesOnReview();
        foreach ($ruleCollection as $rule) {
            $isTransferSuccessful = $this->createPendingTransfer($rule);

            if ($isTransferSuccessful) {
                $initialTransferStatusForReviews = Mage::helper('rewards/config')->getInitialTransferStatusAfterReview();
                $message = ($initialTransferStatusForReviews == 5) 
                    ? 'You received %s for this review'
                    : 'You will receive %s upon approval of this review';
                
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('rewards')->__(
                        $message,
                        (string)Mage::getModel('rewards/points')->set($rule)
                    )
                );
            }
        }

        return $this;
    }
    
    /**
     * Creates a transfer for the next review that matches each rule
     * 
     * @param int $customerId
     */
    public function createTransferForNextReview($customerId)
    {
        // We get the last transfer, so the newly created transfers will not be included in our select
        $lastTransferId = Mage::getModel('rewards/transfer')
            ->getCollection()
            ->setOrder('rewards_transfer_id','DESC')
            ->setPageSize(1)
            ->getFirstItem()
            ->getId();
        
        // Get applicable rules without filtering for current date as we process past data
        Mage::getSingleton('rewards/session')->setSkipDates(true);
        $ruleCollection = Mage::getSingleton('rewards/review_validator')->getApplicableRulesOnReview();
        
        foreach ($ruleCollection as $rule) {
            $fromDate = $rule->getFromDate();
            $endDate = $rule->getToDate();
            
            // Get all review ID's on which the customer has transfers
            $transfers = Mage::getModel('rewards/transfer')
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                // the below table contains the review IDs we are looking for
                ->addFieldToFilter('rewards_transfer_id', array('lteq' => $lastTransferId))
                // we only care about review related transfers
                ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('product_review'));
            $transfers->getSelect()
                // we remove all columns from the select as we only need the review IDs, no need to fetch anything else
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('reference_id');
            
            $includedReviewIds = array();
            foreach ($transfers as $transfer) {
                $includedReviewIds[] = $transfer->getReferenceId();
            }
            
            $nextReview = Mage::getModel('review/review')->getResourceCollection();
            
            if (!empty($fromDate)) {
                $nextReview->addFieldToFilter('created_at', array('gteq' => $fromDate));
            }
            
            if (!empty($endDate)) {
                $nextReview->addFieldToFilter('created_at', array('lteq' => $endDate));
            }
            
            // Fetch next valid review
            $nextReview->addCustomerFilter($customerId)
                ->addFieldToFilter('status_id', array(Mage_Review_Model_Review::STATUS_APPROVED, Mage_Review_Model_Review::STATUS_PENDING))
                ->addFieldToFilter('main_table.review_id', array('nin' => $includedReviewIds))
                ->setOrder('main_table.review_id','ASC')
                ->setPageSize(1);
            
            if ($nextReview->getSize() > 0) {
                $this->createPendingTransfer($rule, $nextReview->getFirstItem());
            } 
              
            Mage::getSingleton('rewards/session')->setSkipDates(false);
        }
    }

    /**
     * Returns a collection of all transfers associated with this review
     *
     * @return array(TBT_Rewards_Model_Transfer) : A collection of all reviews associated with this review
     */
    public function getAssociatedTransfers()
    {
        return Mage::getModel('rewards/review_transfer')->getTransfersAssociatedWithReview($this->getReview()->getId());
    }

    /**
     * Creates a new transfer with a pending status using the rule information
     *
     * @param TBT_Rewards_Model_Special $rule
     */
    public function createPendingTransfer($rule, $review = null)
    {
        if (empty($review)) {
            $review = $this->getReview();
        }
        try {
            $is_transfer_successful = Mage::getModel('rewards/review_transfer')->transferReviewPoints($review, $rule);
        } catch (Exception $ex) {
            Mage::helper('rewards/debug')->log($ex->getMessage());
            Mage::getSingleton('core/session')->addError($ex->getMessage());
        }

        return $is_transfer_successful;
    }

    /**
     * Send review approval email to customer
     * 
     * @return bool
     */
    protected function sendReviewApprovalConfirmationEmail($pointsBeforeReviews, $pointsEarned)
    {
        if (!$this->shouldSendReviewApprovalConfirmationEmail && $pointsEarned > 0) {
            return false;
        }

        $review = $this->getReview();

        // Check if review approval emails are enabled
        $template = Mage::getStoreConfig('rewards/reviews_and_tags/review_approval_confirmation_email_template', $review->getStoreId());
        if ($template === 'none') {
            return false;
        }
        
        $customer = Mage::getModel('rewards/customer')->load($review->getCustomerId());
        $customerStoreId = $customer->getStoreId();

        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $emailHelper = Mage::helper('rewards/email');
        
        $sender = array(
            'name' => strip_tags(Mage::helper('rewards/email')->getSenderName($customerStoreId)),
            'email' => strip_tags(Mage::helper('rewards/email')->getSenderEmail($customerStoreId))
        );

        $totalPoints = $pointsBeforeReviews + $pointsEarned;
        $vars = array(
            'customer_name'         => $customer->getName(),
            'customer_email'        => $customer->getEmail(),
            'store_name'            => $customer->getStore()->getFrontendName(),
            'points_balance'        => Mage::helper('rewards')->getPointsString($totalPoints),
            'pending_points'        => (string) $customer->getPendingPointsSummary(),
            'points_earned'         => Mage::helper('rewards')->getPointsString($pointsEarned),
            'has_pending_points'    => $customer->hasPendingPoints(),
            'review_title'          => $review->getTitle(),
            'review_detail'         => $review->getDetail(),
            'review_created_at'     => $review->getCreatedAt(),
            'product'               => Mage::getModel('catalog/product')->load($review->getEntityPkValue())->getName()
        );
        
        $result = $emailHelper->sendTransactional($template, $sender, $customer, $vars);
        $translate->setTranslateInline(true);

        $this->shouldSendReviewApprovalConfirmationEmail = false;
        return $result;
    }
}
