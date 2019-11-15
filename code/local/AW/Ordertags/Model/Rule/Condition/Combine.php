<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Ordertags
 * @version    1.3.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

class AW_Ordertags_Model_Rule_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('ordertags/rule_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        $hlp = Mage::helper('ordertags');
        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            array(
                 array(
                     'value' => 'ordertags/rule_condition_combine',
                     'label' => $hlp->__('Conditions Combination'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|order_total',
                     'label' => $hlp->__('Order Subtotal'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|order_grand_total',
                     'label' => $hlp->__('Order Grand Total'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|order_status',
                     'label' => $hlp->__('Order Status'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|order_items_total',
                     'label' => $hlp->__('Items Total (Invoiced)'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|sku',
                     'label' => $hlp->__('SKU'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|shipping_country',
                     'label' => $hlp->__('Shipping Country'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|billing_country',
                     'label' => $hlp->__('Billing country'),
                 ),
                 array(
                     'value' => 'ordertags/rule_condition_order_params|payment_method',
                     'label' => $hlp->__('Payment Method'),
                 )
            )
        );

        if (0 && Mage::helper('ordertags')->extensionInstalled('AW_Collpur')) {

            $conditions = array_merge_recursive(
                $conditions,
                array(
                     array(
                         'value' => 'ordertags/rule_condition_order_params|order_contains_deal',
                         'label' => $hlp->__('Number of deals in order'),
                     ),
                     array(
                         'value' => 'ordertags/rule_condition_order_params|order_deal_status',
                         'label' => $hlp->__('Deal Status'),
                     ),
                )
            );
        }
        return $conditions;
    }

    public function asHtml()
    {
        $typeHtml = $this->getTypeElement()->getHtml();
        $aggregatorHtml = $this->getAggregatorElement()->getHtml();
        $valueHtml = $this->getValueElement()->getHtml();

        $hlp = Mage::helper('ordertags');

        $html = $typeHtml . $hlp->__("If %s of these order conditions are %s", $aggregatorHtml, $valueHtml);
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }
        return $html;
    }
}
