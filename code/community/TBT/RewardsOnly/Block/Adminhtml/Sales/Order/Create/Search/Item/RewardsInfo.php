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
 * @package    [TBT_RewardsOnly]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Used for rendering Admin Order Create Product Search spending information
 * @package     TBT_RewardsOnly
 * @subpackage  Block
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsOnly_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo
    extends TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo
{
    /**
     * Cached value for validation if product has points-only rules
     * @var null|boolean 
     */
    protected $_hasPointsOnlyRule = null;
    
    /**
     * Cached value for the points-only rule id that will be applied
     * @var null|int 
     */
    protected $_pointsOnlyRuleId = null;
    
    /**
     * Cached value for the points-only rule points value that will be applied
     * @var int 
     */
    protected $_pointsOnlyValue = 0;
    
    /**
     * Cached value for the points-only rule points value string that will be applied
     * @var string 
     */
    protected $_pointsOnlyString = null;
    
    /**
     * Validate if there are rules for this product and not points-only
     * @see \TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo [has the original method]
     * @return boolean
     */
    public function hasRulesProduct()
    {
        $areAnyRules = parent::hasRulesProduct();
        
        if (!$areAnyRules) {
            return false;
        }
        
        if ($this->hasPointsOnlyRules()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate if there are points-only rules for this product
     * @see \TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo [has the original method]
     * @return boolean
     */
    public function hasPointsOnlyRules()
    {
        if (!$this->getProduct() || !$this->getProduct()->getId()) {
            $this->_hasPointsOnlyRule = false;
        }
        
        if ($this->_hasPointsOnlyRule === null) {
            $customer = Mage::getSingleton('rewards/sales_aggregated_cart')
                ->getCustomer();
            
            if (!$customer || !$customer->getId()) {
                return $this->_hasPointsOnlyRule = false;
            }
            
            $ruleParams = $this->getProduct()->getPointsCostParams($customer);
            $poinstOnlyValue = (array) $ruleParams['points'];
            $this->_hasPointsOnlyRule = $ruleParams['hasRule'];
            $this->_pointsOnlyRuleId = $ruleParams['ruleId'];
            $this->_pointsOnlyValue = array_pop($poinstOnlyValue);
            $this->_pointsOnlyString = $ruleParams['pointsString'];
        }

        return $this->_hasPointsOnlyRule;
    }
    
    /**
     * Getter for points-only rule id
     * @return int
     */
    public function getPointsOnlyRuleId()
    {
        return $this->_pointsOnlyRuleId;
    }
    
    /**
     * Getter for points-only points value
     * @return int
     */
    public function getPointsOnlyValue()
    {
        return $this->_pointsOnlyValue;
    }
    
    /**
     * Getter for points-only points string value
     * @return string
     */
    public function getPointsOnlyString()
    {
        return $this->_pointsOnlyString;
    }
}