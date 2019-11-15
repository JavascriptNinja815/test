<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).

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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order Create Search Grid Column Renderer for Price
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Adminhtml_Sales_Order_Create_Search_Grid_Renderer_Price
    extends Mage_Adminhtml_Block_Sales_Order_Create_Search_Grid_Renderer_Price
{
    /**
     * Renderer
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $html = parent::render($row);

        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            return $html;
        }

        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($row);

        if(empty($pointsArr)) {
    		return $html;
    	}

        $rewardsInfoBlock = $this->getLayout()
            ->createBlock('rewardspointsonly/adminhtml_sales_order_create_search_grid_item_rewardsInfo', "rewardspointsonly-info")
            ->setProductRendererHtml($html)
            ->setPointsOnlyInfo($pointsArr)
            ->setProduct($row);

        return $rewardsInfoBlock->toHtml();
    }
}
