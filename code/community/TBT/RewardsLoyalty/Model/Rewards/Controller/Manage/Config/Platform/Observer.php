<?php

class TBT_RewardsLoyalty_Model_Rewards_Controller_Manage_Config_Platform_Observer extends Varien_Object
{
    const KEY_LOYALTY_LAST = TBT_RewardsLoyalty_Helper_Loyalty::KEY_LOYALTY_LAST;

    public function connectPostDispatch($observer)
    {
        $event = $observer->getEvent();
        if (!$event) {
            return $this;
        }

        $action = $event->getControllerAction();
        if (!$action) {
            return $this;
        }

        // reset recurring actions hook if account is being disconnected
        $configKey = Mage::helper('rewardsloyalty/loyalty')->getModuleKey() . self::KEY_LOYALTY_LAST;
        Mage::getConfig()->saveConfig($configKey, '');
        Mage::getConfig()->cleanCache();

        // explicitly calling this so that we instantly re-enable any rules that were disabled by Sweet Tooth
        Mage::helper('rewardsloyalty/loyalty')->onModuleActivity();

        return $this;
    }

    /**
     * Observes when Sweet Tooth account is disconnected and sets last checked config value to current time.
     * We only want to disable earning rules after 24 hours the account was disconnected.
     *
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function disconnectPostDispatch($observer)
    {
        $configKey = Mage::helper('rewardsloyalty/loyalty')->getModuleKey() . self::KEY_LOYALTY_LAST;
        Mage::getConfig()->saveConfig($configKey, time());
        Mage::getConfig()->cleanCache();

        return $this;
    }
}
