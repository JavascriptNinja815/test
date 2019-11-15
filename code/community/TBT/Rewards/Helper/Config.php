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

/**
 * Helper Config
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Config extends Mage_Core_Helper_Abstract
{
    const CONFIG_XPATH_SHOW_REWARDS_DASHBOARD_WIDGET = 'rewards/help/showDashboardStPanel';

    /**
     * Config xpath for discount full summary display flag
     */
    const CONFIG_XPATH_SHOW_CART_DISCOUNT_FULL_SUMMARY = 'rewards/display/show_cart_discount_full_summary';

    /**
     * Checks if Sweet Tooth panel from Dashboard is enabled/disabled
     *
     * @return boolean
     */
    public function displayRewardsDashboardWidget()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_XPATH_SHOW_REWARDS_DASHBOARD_WIDGET);
    }

    /**
     * Check if cart discount full summary should be displayed
     * @return boolean
     */
    public function displayCartDiscountFullSummary()
    {
        return (bool) Mage::getStoreConfigFlag(self::CONFIG_XPATH_SHOW_CART_DISCOUNT_FULL_SUMMARY);
    }

    public function getInitialTransferStatusAfterOrder()
    {
        return Mage::getStoreConfig ( 'rewards/InitialTransferStatus/AfterOrder' );
    }

    public function getInitialTransferStatusAfterReview()
    {
        return Mage::getStoreConfig ( 'rewards/InitialTransferStatus/AfterReview' );
    }

    public function getInitialTransferStatusAfterPoll()
    {
        return Mage::getStoreConfig ( 'rewards/InitialTransferStatus/AfterPoll' );
    }

    public function getInitialTransferStatusAfterSendfriend()
    {
        return Mage::getStoreConfig ( 'rewards/InitialTransferStatus/AfterSendFriend' );
    }

    public function getInitialTransferStatusAfterTag()
    {
        return Mage::getStoreConfig ( 'rewards/InitialTransferStatus/AfterTag' );
    }

    /**
     * @deprecated use TBT_Rewards_Helper_Newsletter_Config instead
     */
    public function getInitialTransferStatusAfterNewsletter()
    {
        return Mage::helper ( 'rewards/newsletter_config' )->getInitialTransferStatusAfterNewsletter ();
    }

    public function getInitialTransferStatusAfterSignup()
    {
        return Mage::getStoreConfig ( 'rewards/InitialTransferStatus/AfterSignup' );
    }

    /**
     * Not sure what this is and we're not using it so let's leave
     * it as true for now.
     * NOTE: Nothing in the system is currently using this, but its purpose is
     * simply to check whether or not the TBT_Rewards module is currently active
     * and/or its output is enabled. This would be useful in cart.phtml and such.
     * @deprecated until we can figure out what place in the system uses this.
     *
     * @return boolean
     */
    public function getIsCustomerRewardsActive()
    {
        //return Mage::getStoreConfigFlag('wishlist/general/active');
        //return Mage::getStoreConfigFlag('rewards/active');
        // Mage::getConfig()->getModuleConfig('TBT_Rewards')->is('active', true)
        return true;
    }

    public function shouldRemovePointsOnCancelledOrder()
    {
        return Mage::getStoreConfig ( 'rewards/orders/shouldRemovePointsOnCancelledOrder' );
    }

    /**
     * Check if points should be approved on invoice
     * @return boolean
     */
    public function shouldApprovePointsOnInvoice()
    {
        return (bool) (Mage::getStoreConfig ('rewards/orders/shouldApprovePointsOn')
            == TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn::APPROVE_ORDER_POINTS_ON_INVOICE);
    }

    /**
     * Check if points should be approved on shipment
     * @return boolean
     */
    public function shouldApprovePointsOnShipment()
    {
        return (bool) (Mage::getStoreConfig ('rewards/orders/shouldApprovePointsOn')
            == TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn::APPROVE_ORDER_POINTS_ON_SHIPMENT);
    }

    /**
     * Validate if points should be approved on order complete
     * @return boolean
     */
    public function shouldApprovePointsOnOrderComplete()
    {
        return (bool) (Mage::getStoreConfig ('rewards/orders/shouldApprovePointsOn')
            == TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn::APPROVE_ORDER_POINTS_ON_ORDER_COMPLETE);
    }

    public function canHaveNegativePtsBalance()
    {
        return Mage::getStoreConfig ( 'rewards/general/canHaveNegativePtsBalance' );
    }

    public function getDefaultMassTransferComment()
    {
        return Mage::getStoreConfig ( 'rewards/transferComments/defaultMassTransferComment' );
    }

    public function getLKey()
    {
        return Mage::getStoreConfig ( 'rewards/registration/lKey' );
    }

    public function getCompanyName()
    {
        return Mage::getStoreConfig ( 'rewards/registration/companyName' );
    }

    public function getCompanyPhoneNumber()
    {
        return Mage::getStoreConfig ( 'rewards/registration/companyPhoneNumber' );
    }

    public function getSimulatedPointMax()
    {
        return 1000000000;
    }

    /**
     * True if the customer can use points when they're not logged in.
     * For example if the customer cannot use points when not logged in
     * they will be asked to login before selecting an option in the
     * redemptions catalog drop box.
     *
     * @return boolean
     */
    public function canUseRedemptionsIfNotLoggedIn()
    {
        return Mage::getStoreConfigFlag ( 'rewards/general/canUseRedemptionsIfNotLoggedIn' );
    }

    public function showCartRedemptionsWhenZero()
    {
        return Mage::getStoreConfigFlag ( 'rewards/display/showCartRedemptionsWhenZero' );
    }

    public function showCartDistributionsWhenZero()
    {
        return Mage::getStoreConfigFlag ( 'rewards/display/showCartDistributionsWhenZero' );
    }

    public function showSidebarIfNotLoggedIn()
    {
        return Mage::getStoreConfigFlag ( 'rewards/display/showSidebarIfNotLoggedIn' );
    }

    public function showSidebar()
    {
        return Mage::getStoreConfigFlag ( 'rewards/display/showSidebar' );
    }

    /**
     * @deprecated If the admin wants to hide the redeemer just make the redemption rule
     * not apply to any customers that are not logged in.
     *
     * @return boolean
     */
    public function showRedeemerWhenNotLoggedIn()
    {
        return true;
    }

    public function showProductEditPointsTab()
    {
        return false;
    }

    public function showCustomerEditPointsTab()
    {
        return true;
    }

    /**
     * @deprecated rules now have a getApplyToShipping attribute
     *
     * @param unknown_type $store
     * @return unknown
     */
    public function discountShipping($store = null)
    {
        return Mage::getStoreConfigFlag ( 'rewards/general/shopping_cart_rule_discount_shipping', $store );
    }

    //@nelkaake Added on Wednesday May 5, 2010:
    public function calcCartPointsAfterDiscount($store = null)
    {
        return Mage::getStoreConfigFlag ( 'rewards/general/shopping_cart_rule_earn_after_discount', $store );
    }

    public function maximumPointsSpentInCart()
    {
        return ( int ) Mage::getStoreConfig ( 'rewards/general/maximum_points_spent_in_cart' );
    }

    public function disableCheckoutsIfNotEnoughPoints()
    {
        return true;
    }

    public function getSenderName($storeId)
    {
        return Mage::getStoreConfig ( "trans_email/ident_".$this->getCustomSender( $storeId )."/name", $storeId );
    }

    public function getSenderEmail($storeId)
    {
        return Mage::getStoreConfig ( "trans_email/ident_".$this->getCustomSender( $storeId )."/email", $storeId );
    }

    /*
    Option to select email sender for all ST emails
    */

    public function getCustomSender($storeId = null)
    {
        if ($customSender = Mage::getStoreConfig("rewards/general/rewards_custom_email", $storeId )) {
            return $customSender;
        }

        return "support";
    }

    /**
     * KJ: Set it on a Singleton, b/c otherwise
     * when the other fields are saved, they will overwrite the value that was
     * just saved.  This is a tiny bit hacky but there didn't seem to be any
     * other way from preventing the overwrite from happening.  We could simply
     * move this field past the other fields in sort order, which would cause
     * this to execute last, but that seems brittle b/c simply changing sort
     * order would break it.
     *
     */
    public function setConfigValue($key, $value)
    {
        $values = Mage::getSingleton('core/website')->getRewardsConfigValueOverrides();
        if (!is_array($values)) {
            $values = array();
        }

        $values[$key] = $value;
        Mage::getSingleton('core/website')->setRewardsConfigValueOverrides($values);
    }

    public function getConfigValue($key)
    {
        $values = Mage::getSingleton('core/website')->getRewardsConfigValueOverrides();
        if (isset($values[$key])) {
            return $values[$key];
        }
    }

    /**
     * Will attempt to disable a module given a full module key
     * @param string $module_key, eg. Enterprise_Reward
     * @return boolean true if successful, false otherwise
     */
    public function disableModule($module_key) 
    {
    	try {
    		$configFile = Mage::getBaseDir('etc'). DS . 'modules' . DS . $module_key . '.xml';
    
    		if(!file_exists($configFile)) {
    			return false;
    		}

            $disabled = @rename($configFile, $configFile. '.disabled');
            return $disabled && file_exists($configFile. '.disabled');

    	} catch(Exception $e) {
    		return false;
    	}
    }
    
    /**
     * Validates if config paths are true
     * @param string|arrau $ifConfigPaths
     * @param boolean $emptyIsValid
     * @return boolean
     */
    public function ifConfigLayout($ifConfigPaths, $emptyIsValid = true)
    {
        if (empty($ifConfigPaths)) {
            return $emptyIsValid;
        }
        
        if (!is_array($ifConfigPaths)) {
            $ifConfigPaths = array($ifConfigPaths);
        }
        
        $foundInvalidConfig = false;
        
        foreach ($ifConfigPaths as $ifConfigPath) {
            if (!empty($ifConfigPath) && !Mage::getStoreConfigFlag($ifConfigPath)) {
                $negation = strpos($ifConfigPath, '!');

                if ($negation === false) {
                    if (!Mage::getStoreConfigFlag($ifConfigPath)) {
                        $foundInvalidConfig = true;
                        break;
                    }
                } else {
                    $ifConfigPath = str_replace('!', '', $ifConfigPath);
                    
                    if (Mage::getStoreConfigFlag($ifConfigPath)) {
                        $foundInvalidConfig = true;
                        break;
                    }
                }
            }
        }
        
        if ($foundInvalidConfig) {
            return false;
        }
        
        return true;
    }
    
    /**
    * Can points be adjusted?
    * @return bool
    */
    public function canAdjustPoints()
    {
        return (bool)Mage::getStoreConfig('rewards/checkout/adjust_points_cancelation');
    }

    /**
     * Can Display Earnings Prediction based on config
     * @return boolean
     */
    public function canDisplayPredictionForEarnings()
    {
        return (bool) Mage::getStoreConfigFlag('rewards/display/show_earning_prediction_in_catalog');
    }

    /**
     * Can Display Spendings Prediction based on config
     * @return boolean
     */
    public function canDisplayPredictionForSpendings()
    {
        return false;
    }
}

