<?php

class TBT_RewardsSocial2_Model_Special_Config_PurchaseShareTwitter extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_purchase_share_twitter';

    public function _construct()
    {
        $this->setCaption("Purchase Share");
        $this->setDescription("Customer will be rewarded for sharing their purchase on social networks.");
        $this->setCode("social_purchase_share");

        return parent::_construct();
    }
    
    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__('Shares a purchase on Twitter.'),
        );
    }
    
    public function getNewActions()
    {
        return array();
    }
}
