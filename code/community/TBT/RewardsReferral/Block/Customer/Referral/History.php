<?php

class TBT_RewardsReferral_Block_Customer_Referral_History extends TBT_RewardsReferral_Block_Customer_Referral_Abstract {

    public function _prepareLayout() {
        parent::_prepareLayout();
        $pager = $this->getLayout()->createBlock('page/html_pager', 'rewardsref.referral')
                ->setCollection($this->getReferred());
        $this->setChild('pager', $pager);
        return $this;
        //return parent::_prepareLayout();
    }

    public function getPagerHtml() {
        return $this->getChildHtml('pager');
    }

    protected function isMessageSentWithNoResponse($referral)
    {
        return (bool) $referral->getReferralStatus() == TBT_RewardsReferral_Model_Referral::STATUS_REFERRAL_SENT;
    }

    /**
     * Resend Link
     * @param Mage_Customer_Model_Customer $referral
     * @return type
     */
    protected function getResendUrl($referral)
    {
        $isSecure = (bool) Mage::app()->getStore()->isCurrentlySecure();
        $hashIdsHelper = Mage::helper('rewards/hashids');

        $code = $hashIdsHelper->cryptIds($referral->getId());

        return $this->getUrl('rewardsref/customer/resendInvite/id/' . $code, array('_secure' => $isSecure));
    }
}