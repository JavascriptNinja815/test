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
 */

/**
 * Tracking Helper
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */

$dependency = Mage::getBaseDir('lib') . DS. 'SweetTooth' . DS . 'Mixpanel' . DS . 'lib' . DS . 'Mixpanel.php';
if (file_exists($dependency) && is_readable($dependency)) {
    include_once($dependency);
} else {
    $message = Mage::helper('rewards')->__("Wasn't able to load some dependencies.");
    Mage::getSingleton('core/session')->addError($message);
    Mage::helper('rewards/debug')->log($message);
    return $this;
}

class TBT_Rewards_Helper_Tracking extends Mage_Core_Helper_Abstract 
{
    const EVENT_CONNECT = 'connect';
    const EVENT_INSTALL = 'install';
    const EVENT_QUICK_LAUNCH = 'quicklaunch';
    const EVENT_RULE_SAVE = 'rule_save';
    const EVENT_DASHBOARD_VIEW = 'dashboard_view';
    
    protected $instance;

    /**
     * Constructor. Initialize Mixpanel and register general data
     * @return \TBT_Rewrads_Helper_Tracking
     */
    public function __construct()
    {
        if (!$this->instance) {
            $token = $this->getToken();
            if (empty($token)) {
                return $this;
            }
            
            $this->instance = Mixpanel::getInstance($token);
            $this->instance->identify($this->getIdentity());
            $this->instance->registerAll($this->getGlobalData());
        }
        
        return $this;
    }
    
    /**
     * Fetch Mixpanel token id
     * return @string
     */
    public function getToken()
    {
        return Mage::getStoreConfig('rewards/mixpanel/key');
    }
    
    /**
     * Fetch Mixpanel Global Data (this data is sent on every event)
     * @return array
     */
    public function getGlobalData()
    {
        $username = Mage::getStoreConfig('rewards/platform/username');
        $isDevMode = ($username) ? Mage::getStoreConfig('rewards/platform/dev_mode') : null;
        
        return array(
            'mage_url' => Mage::getBaseUrl(),
            'mage_version' => Mage::getVersion(),
            'mage_edition' => strtolower($this->getMagentoEdition()),
            'st_version' => Mage::helper('rewards')->getExtensionVersion(),
            'st_username' => $username,
            'st_dev_mode' => (bool) $isDevMode
        );
    }
    
    /**
     * Get magento edition
     * @return string
     */
    public function getMagentoEdition()
    {
        if (method_exists('Mage', 'getEdition')) {
            return Mage::getEdition();
        } else {
            return (Mage::helper("rewards/version")->isMageEnterprise()) ? 'Enterprise' : 'Community';
        }
    }
    
    /**
     * Fetch Mixpanel Identity
     * return @string
     */
    public function getIdentity()
    {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $formatedUrl = parse_url(strtolower($url));
        return $formatedUrl['host'];
    }
    
    /**
     * Track rule data after a save
     * 
     * @param string $type
     * @param Mage_Rule_Model_Rule $rule
     */
    public function sendRuleData($type, $rule)
    {
        if (Mage::registry('st_disable_rule_save_tracking')) {
            return $this;
        }
        
        $action = ($rule->isDistributionRule()) ? 'earning' : 'spending';
        if ($rule->getPointsAction() === TBT_Rewards_Model_Special_Action::ACTION_TYPE_CUSTOMER_GROUP) {
            $action = TBT_Rewards_Model_Special_Action::ACTION_TYPE_CUSTOMER_GROUP;
        }
        
        $conditions = Mage::helper('rewards')->unhashIt($rule->getConditionsSerialized());
        $data = array(
            'name' => $rule->getName(),
            'type' => $type,
            'action' => $action,
            'enabled' => (bool) $rule->getIsActive(),
            'points-only' => (bool) $rule->getPointsOnlyMode(),
            'has-coupon' => ($rule->getCouponType() == 2),
            'behavior-type' => reset($conditions)
        );
        
        $this->track(self::EVENT_RULE_SAVE, $data);
    }

    /**
     * Track event in Mixpanel
     * 
     * @param string $event
     * @param array $data
     */
    public function track($event, $data = array())
    {
        if ($this->instance) {
            try {
                $this->instance->track($event, $data);
                $this->instance->flush();
            } catch (Exception $e) {
                Mage::helper('rewards/debug')->log($e->getMessage());
            }
        }
    }
}

