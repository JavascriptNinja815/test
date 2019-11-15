<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 *
 * @category   [TBT]
 * @package    [TBT_RewardsReferral]
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Url generator/parser helper
 *
 * @category   TBT
 * @package    TBT_RewardsReferral
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Helper_Url extends Mage_Core_Helper_Abstract
{
    /**
     * Return referral url by customer
     * Note: Url email and id styles are deprecated, code is used instead
     * @param Mage_Customer_Model_Customer $customer
     * @return string
     */
    public function getUrl(Mage_Customer_Model_Customer $customer)
    {
        $hashIdsHelper = Mage::helper('rewards/hashids');

        switch (Mage::helper('rewardsref')->getReferralUrlStyle()) {
            case TBT_RewardsReferral_Helper_Data::REWARDSREF_URL_STYLE_EMAIL:
                $urlData = array(
                    '_query' => array('email' => urlencode($customer->getEmail()))
                );
                break;
            case TBT_RewardsReferral_Helper_Data::REWARDSREF_URL_STYLE_CODE:
                $urlData = array(
                    '_query' => array(
                        'st-code' => urlencode(Mage::helper('rewardsref/code')->getCode($customer->getId()))
                    )
                );
                break;
            default:
                $urlData = array(
                    '_query' => array('st-id' => $customer->getId())
                );
        }

        $urlData['_use_rewrite'] = true;
        $urlData['_secure'] = true;
        
        if (Mage::app()->getStore()->isAdmin()) {
            $websiteId = $customer->getWebsiteId();
            if (empty($websiteId)) {
                $websiteId = true;
            }
            
            $urlData['_store'] = Mage::app()
                ->getWebsite($websiteId)
                ->getDefaultGroup()
                ->getDefaultStoreId();
        }

        $url = Mage::getUrl('refer/', $urlData);

        return $url;
    }

    /**
     * Getter for Current Url with Referrer param
     * @param Mage_Customer_Model_Customer $customer
     * @return string
     */
    public function getCurrentUrlWithReferrer($customer)
    {
        $request = Mage::app()->getRequest();

        $fullActionName = $request->getRequestedRouteName().'_'.
            $request->getRequestedControllerName().'_'.
            $request->getRequestedActionName();

        if (strpos($fullActionName, 'rewardsref_customer') !== false) {
            return $this->getUrl($customer);
        }
        
        $isCurrentlySecure = Mage::app()->getStore()->isCurrentlySecure();

        $customerCode = urlencode(Mage::helper('rewardsref/code')->getCode($customer->getId()));

        return Mage::app()->getStore()->getUrl(
            '*/*/*/',
            array(
                '_use_rewrite' => true,
                '_secure' => $isCurrentlySecure,
                '_query' => array('st-code' => $customerCode)
            )
        );
    }
    
}
