<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
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
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class TBT_Rewards_Helper_Email extends Mage_Core_Helper_Abstract
{
    public function getSenderName($storeId)
    {
        $customSender = Mage::helper('rewards/config')->getCustomSender($storeId);
        return Mage::getStoreConfig ( "trans_email/ident_" . $customSender . "/name", $storeId );
    }

    public function getSenderEmail($storeId)
    {
        $customSender = Mage::helper('rewards/config')->getCustomSender($storeId);
        return Mage::getStoreConfig ( "trans_email/ident_" . $customSender . "/email", $storeId );
    }
    
    /**
     * Fetch the unsubscribe url
     * 
     * @param Mage_Customer_Model_Customer $customer
     * @return string (url)
     */
    public function getUnsubscribeUrl($customer)
    {
        if (!is_a($customer, 'Mage_Customer_Model_Customer')) {
            $customer = Mage::getModel('customer/customer')->load($customer->getId());
        }
        
        $storeId = ($customer->getStoreId()) 
            ? $customer->getStoreId() 
            : Mage::app()->getWebsite($customer->getWebsiteId())->getDefaultGroup()->getDefaultStoreId();

        return Mage::getUrl('rewards/customer_notifications/unsubscribe/', array('_store' => $storeId))
            . 'customer/' . urlencode(base64_encode($customer->getId()));
    }
    
    /**
     * Send Sweet Tooth Email (will check if email notifications are enabled for the client)
     * 
     * @param string $template
     * @param array $sender
     * @param TBT_Rewards_Model_Customer $customer
     * @param array $vars
     * 
     * @return bool
     */
    public function sendTransactional($template, $sender, $customer, $vars)
    {
        if (!$customer || !$customer->getId()) {
            return false;
        }
        
        if ($customer->hasRewardsPointsNotification() && !$customer->getRewardsPointsNotification()) {
            return false;
        }
        
        $email = Mage::getModel('core/email_template');
        $email->setDesignConfig(array(
            'area' => 'frontend',
            'store' => $customer->getStoreId())
        );
        
        $vars['points_caption'] = Mage::helper('rewards/currency')->getDefaultFullCurrencyCaption();
        $vars['unsubscribe_url'] = $this->getUnsubscribeUrl($customer);
        $email->sendTransactional($template, $sender, $customer->getEmail(), $customer->getName(), $vars);
        
        if (!$email->getProcessedTemplate()) {
            $message = Mage::helper('rewards')->__("The email template was not properly loaded. This might be due to locale issues with characters that could be read, or tempalte variables that could not be parsed properly. The template being loaded was %s", $template);
            throw new Exception($message);
        }
        
        return $email->getSentSuccess();
    }
}
