<?php

class TBT_Rewards_Model_Poll_Transfer extends TBT_Rewards_Model_Transfer
{
    public function _construct()
    {
        parent::_construct();
    }
    
    /**
     * Fetch transfers associated with the poll
     * @param int $poll_id
     * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
     */
    public function getTransfersAssociatedWithPoll($poll_id) {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('poll'))
            ->addFieldToFilter('reference_id', $poll_id);
    }

    /**
     * Creates a customer point-transfer of any amount or currency.
     *
     * @param  TBT_Rewards_Model_Poll_Decorators_Poll $rpoll
     * @param  $rule Special Rule
     * @return boolean  : whether or not the point-transfer succeeded
     */
    public function createPollPoints(TBT_Rewards_Model_Poll_Decorators_Poll $rpoll, $rule)
    {

        $num_points = $rule->getPointsAmount();
        $rule_id = $rule->getId();
        $transfer = $this->initTransfer( $num_points, $rule_id );
        $customer = $rpoll->getRewardsCustomer();
        $store_id = $customer->getStore()->getId();

        if (! $transfer) {
            return false;
        }

        $initial_status = Mage::helper( 'rewards/poll_config' )->getInitialTransferStatusAfterPoll( $store_id );

        if (! $transfer->setStatusId( null, $initial_status )) {
            return false;
        }

        $initial_transfer_msg = Mage::getStoreConfig( 'rewards/transferComments/pollEarned', $store_id );
        $comments = Mage::helper( 'rewards' )->__( $initial_transfer_msg );

        $customer_id = $rpoll->getCustomerId();

        $this->setPollId( $rpoll->getPollId() )
            ->setReferenceId($rpoll->getPollId())
            ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('poll'))
            ->setComments( $comments )
            ->setCustomerId( $customer_id )
            ->save();

        return true;
    }
}