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
 * Used for rendering Admin Order Create Product Search spending information
 * @package     TBT_Rewards
 * @subpackage  Block
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Rewards Catalog Product Instance
     * @var TBT_Rewards_Model_Catalog_Product|null 
     */
    protected $_product;
    
    /**
     * Main Constructor
     */
    public function _construct()
    {
        parent::_construct();
        
        $this->setTemplate("rewards/sales/order/create/search/item/rewards_info.phtml");
    }
    
    /**
     * Setter for product
     * @param TBT_Rewards_Model_Catalog_Product $product
     * @return \TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo
     */
    public function setProduct($product)
    {
        $this->_product = $this->_ensureProduct($product);
        
        return $this;
    }
    
    /**
     * Getter for product
     * @return TBT_Rewards_Model_Catalog_Product|null
     */
    public function getProduct()
    {
        return $this->_product;        
    }
    
    /**
     * Wrap magento product to rewards instance
     * @param TBT_Rewards_Model_Catalog_Product $product
     * @return \TBT_Rewards_Model_Catalog_Product
     */
    protected function _ensureProduct($product)
    {
        if ($product instanceof TBT_Rewards_Model_Catalog_Product) {
            return $product;
        }
        
        $rewardsProduct = Mage::getModel('rewards/catalog_product')
            ->setData($product->getData());
        
        return $rewardsProduct;
    }
    
    /**
     * Product needs to be or it can be configurable with options
     * @return boolean
     */
    public function canConfigure()
    {
        if ($this->getProduct() && $this->getProduct()->canConfigure()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate if there are rules for this product
     * @return boolean
     */
    public function hasRulesProduct()
    {
        $customer = Mage::getSingleton('rewards/sales_aggregated_cart')
                ->getCustomer();
        $applicableRules = $this->getProduct()->getCatalogRedemptionRules($customer);
        
        if (!$applicableRules || count($applicableRules) == 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate if there are points-only rules for this product
     * @see \TBT_RewardsOnly_Block_Adminhtml_Sales_Order_Create_Search_Item_RewardsInfo [It overrides this method]
     * @return boolean
     */
    public function hasPointsOnlyRules()
    {
        return false;
    }

    /**
     * Checks if redemption is allowed based on product type
     * @return boolean
     */
    public function isRedemptionAllowedByProductType()
    {
        if ($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            return false;
        }

        return true;
    }
}