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
class TBT_Rewards_Model_Review_Observer extends Varien_Object
{

    /**
     * The wrapped review
     * @var TBT_Rewards_Model_Review_Wrapper
     */
    protected $_review;

    /**
     * @var array
     */
    protected $_oldData;

    /**
     * @var TBT_Rewards_Model_Review_Wrapper
     */
    protected $_wrapperModel;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_wrapperModel = Mage::getModel('rewards/review_wrapper');
    }

    /**
     * Manages the after_load observer
     *
     * @param Varien_Event_Observer $o
     *
     * @return TBT_Rewards_Model_Review_Observer
     */
    public function afterLoadReview(Varien_Event_Observer $o)
    {
        $review = ($o->getEvent()->hasObject()) ? $o->getEvent()->getObject() : $o->getEvent()->getDataObject();
        if (!($review instanceof Mage_Review_Model_Review)) {
            return $this;
        }

        //Before you save, pass all current data into a dummy model for comparison later.
        $this->_oldData = $review->getData();

        return $this;
    }

    /**
     * Manages the after_save observer
     *
     * @param Varien_Event_Observer $o
     *
     * @return TBT_Rewards_Model_Review_Observer
     */
    public function afterSaveReview(Varien_Event_Observer $o)
    {
        $review = ($o->getEvent()->hasObject()) ? $o->getEvent()->getObject() : $o->getEvent()->getDataObject();
        if (!($review instanceof Mage_Review_Model_Review)) {
            return $this;
        }

        $review = $this->_wrapperModel->wrap($review);
        //If the review becomes approved, approve all associated pending transfers
        if ($this->_oldData['status_id'] == Mage_Review_Model_Review::STATUS_PENDING && $review->isApproved()) {
            $review->approvePendingTransfers();
        } elseif ($this->_oldData ['status_id'] == Mage_Review_Model_Review::STATUS_PENDING && $review->isNotApproved()
        ) {
            $review->discardPendingTransfers();
        } elseif ($review->getReview()->getReviewId() && !isset ($this->_oldData['review_id'])) {
            //If the review is new (hence not having an id before) get applicable rules and create a
            // pending transfer for each one
            $review->ifNewReview();
        }

        return $this;
    }

    /**
     * @return TBT_Rewards_Model_Review_Wrapper
     */
    protected function _getReview()
    {
        return $this->_review;
    }

    /**
     * @return TBT_Rewards_Model_Review_Validator
     */
    protected function _getReviewValidator()
    {
        return Mage::getSingleton('rewards/review_validator');
    }

}
