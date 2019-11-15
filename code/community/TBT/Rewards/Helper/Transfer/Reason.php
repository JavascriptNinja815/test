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
 * Transfer Reason Helper
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Transfer_Reason extends Mage_Core_Helper_Abstract 
{
    protected static $_reasonsById = null;
    protected static $_reasonsByCode = null;

    public function __construct()
    {
        if (is_null(self::$_reasonsByCode) || is_null(self::$_reasonsById)) {
            $this->loadConfigData();
        }
    }

    /**
     * Will read config data and populate a set of static reasons and reason data to be used by this class.
     * @throws Exception if config data is corrupt
     */
    protected function loadConfigData()
    {
        $reasonsById = array();
        $reasonsByCode = array();

        $reasonsConfig = Mage::getConfig()->getNode('rewards/transfer/reason')->asArray();
        foreach ($reasonsConfig as $reasonCode => $reasonData) {
            if (isset($reasonData['reason_id'])) {
                $reasonId = $reasonData['reason_id'];
            } else {
                if (empty($reasonsByCode[$reasonCode]['reason_id'])) {
                    throw new Exception("Unrecognized Transfer Reason code: {$reasonCode}");
                }
                $reasonId = $reasonsByCode[$reasonCode]['reason_id'];
            }

            $defaultData = array(
                'label'             => "",
                'reference_model'   => null
            );
            
            $oldData = isset($reasonsByCode[$reasonCode]) ? $reasonsByCode[$reasonCode] : array();
            $reasonData['reason_id'] = $reasonId;
            $reasonData['reason_code'] = $reasonCode;
            $reasonData = array_merge($defaultData, $oldData, $reasonData);
            $reasonsById[$reasonId] = $reasonData;
            $reasonsByCode[$reasonCode] = $reasonData;

            self::$_reasonsById = $reasonsById;
            self::$_reasonsByCode = $reasonsByCode;
        }
        
        return $this;
    }

    /**
     * Fetch reason data
     * 
     * @param int|string $reason - Reason Code or ID
     * @return array - Reason Data
     * @throws Exception if specified reason not found
     */
    public function getReasonData($reason)
    {
        if (empty(self::$_reasonsById[$reason]) && empty(self::$_reasonsByCode[$reason])) {
            Mage::log("Unrecognized Transfer Reason: {$reason}", null, "rewards.log");
        }
        
        if (is_numeric($reason)) {
            $reasonData = self::$_reasonsById[$reason];
        } else {
            $reasonData = self::$_reasonsByCode[$reason];
        }
        
        return $reasonData;
    }

    /**
     * Fetch reason label
     * 
     * @param int|string $reason - Reason Code or ID
     * @return string - translated label
     * @throws Exception if specified reason not found
     */
    public function getReasonLabel($reason)
    {
        $data = $this->getReasonData($reason);
        return $this->__($data['label']);
    }

    /**
     * Fetch reason ID
     * 
     * @param int|string $reason - Reason Code or ID
     * @return int - Reason ID
     * @throws Exception if specified reason not found
     */
    public function getReasonId($reason)
    {
        $data = $this->getReasonData($reason);
        return $this->__($data['reason_id']);
    }

    /**
     * Fetch reason code
     * 
     * @param int|string $reason - Reason Code or ID
     * @return string - Reason Code
     * @throws Exception if specified reason not found
     */
    public function getReasonCode($reason)
    {
        $data = $this->getReasonData($reason);
        return $this->__($data['reason_code']);
    }

    /**
     * Fetch Reason Reference Model
     * 
     * @param int|string $reason - Reason Code or ID
     * @return null|Mage_Core_Model_Abstract - instantiated reference model class
     * @throws Exception if specified reason not found
     */
    public function getReasonReferenceModel($reason)
    {
        $data = $this->getReasonData($reason);
        
        if (isset($data['reference_model'])) {
            return Mage::getModel($data['reference_model']);
        }
        
        return null;
    }
    
    /**
     * Fetch Reason Reference Model as String
     * 
     * @param int|string $reason - Reason Code or ID
     * @return null|string - reference model class
     * @throws Exception if specified reason not found
     */
    public function getReasonReferenceModelAsString($reason)
    {
        $data = $this->getReasonData($reason);
        
        if (isset($data['reference_model'])) {
            return $data['reference_model'];
        }
        
        return null;
    }
    
    /**
     * Fetch a map of all reasons
     * @return array(id => label)
     */
    public function getAllReasons()
    {
        $reasons = array();
        foreach (self::$_reasonsById as $id => $data) {
            $reasons[$id] = $data['label'];
        }
        
        return $reasons;
    }
    
    /**
     * Fetch the admin URL based on a reason
     * 
     * @param int|string $reason
     * @param int $referenceId
     * @return string|null
     */
    public function getAdminReferenceUrl($reason, $referenceId)
    {
        switch ($this->getReasonReferenceModelAsString($reason)) {
            case ('sales/order'):
                return Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $referenceId));
            case ('catalog/product'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product/edit/index/id', array('id' => $referenceId));
            case ('customer/customer'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/customer/edit/index/id', array('id' => $referenceId));
            case ('rewards/transfer'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/manage_transfer/edit/id', array('id' => $referenceId));
            case ('review/review'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product_review/edit/id', array('id' => $referenceId));
            case ('tag/tag'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/catalog_search/edit/id', array('id' => $referenceId));
            case ('poll/poll'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/poll/edit/id', array('id' => $referenceId));
            case ('tbtmilestone/rule_log'):
                return Mage::helper('adminhtml')->getUrl('adminhtml/manage_history/view/id', array('id' => $referenceId));
            case ('newsletter/subscriber'):
            case ('rewardssocial2/action'):
            default:
                return null;
        }
    }
}

