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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Used for rendering customer balance fieldset in Admin Order Creation
 * @package     TBT_Rewards
 * @subpackage  Block
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Customer_Balance
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Customer Usable Points
     * @return array|int
     */
    public function getCustomerUsablePoints()
    {
        $customer = Mage::getSingleton('rewards/sales_aggregated_cart')
                ->getCustomer();
        
        if ($customer && $customer->getId()) {
            return $customer->getUsablePoints();
        }
        
        return 0;
    }
    
    /**
     * Converts Customer Usable Points to a printable string
     * @return string
     */
    public function getCustomerUsablePointsString()
    {
        $pointsAdjuster = Mage::getModel('rewards/points')
            ->add($this->getCustomerUsablePoints())
            ->setFormatPoints(false);
        
        return $pointsAdjuster->getRendering();
    }
    
    /**
     * Appends the html for this block to the parent block
     * @param null|string $ifConfigPath
     * @return \TBT_Rewards_Block_Adminhtml_Sales_Order_Create_Customer_Balance
     */
    public function appendBlockHtmlToParent($ifConfigPath = null)
    {
        $layoutAppend = Mage::getModel('rewards/helper_layout_action_append')
            ->setParentBlock($this->getParentBlock())
            ->setIfConfig($ifConfigPath)
            ->add($this, 'before')
            ->append();
        
        return $this;
    }
}
