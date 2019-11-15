<?php

class TBT_RewardsSocial2_Model_Transfer_Reference extends TBT_Rewards_Model_Transfer_Reference_Abstract
{
    const REFERENCE_TYPE_ID = 548;
    const REFERENCE_KEY = 'action_id';

    public function clearReferences(&$transfer)
    {
        if ($transfer->hasData(self::REFERENCE_KEY)) {
            $transfer->unsetData(self::REFERENCE_KEY);
        }

        return $this;
    }

    public function getReferenceOptions()
    {
        $referenceOptions = array(self::REFERENCE_TYPE_ID => Mage::helper('rewardssocial2')->__('Social Action'));
        return $referenceOptions;
    }

    /**
     * @see TBT_Rewards_Model_Transfer_Reference_Abstract::loadReferenceInformation()
     */
    public function loadReferenceInformation(&$transfer)
    {
        $this->loadTransferId($transfer);
        return $this;
    }

    /**
     * @param TBT_Rewards_Model_Transfer $transfer
     */
    public function loadTransferId($transfer)
    {
        $id = $transfer->getReferenceId();
        $transfer->setReferenceType(self::REFERENCE_TYPE_ID);
        $transfer->setReferenceId($id);
        $transfer->setData(self::REFERENCE_KEY, $id);

        return $this;
    }


}