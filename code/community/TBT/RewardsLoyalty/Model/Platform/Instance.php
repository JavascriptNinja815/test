<?php

class TBT_RewardsLoyalty_Model_Platform_Instance extends TBT_Rewards_Model_Platform_Instance
{
    public function loyalty() {
        include_once(Mage::getBaseDir('lib') . DS . 'SweetTooth' . DS . 'classes' . DS . 'Loyalty.php');
        return new SweetToothLoyalty($this);
    }
}
