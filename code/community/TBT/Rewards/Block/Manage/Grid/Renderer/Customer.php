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
 * Customer Renderer for customer points grid
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Grid_Renderer_Customer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Contains a list of customers
     *
     * @var array
     */
    protected $_customers = array ();

    /**
     * Renderer of the customer name
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $str = '';
        if ($cid = $row->getId ()) {
            if ($customer = $this->_getCustomer ( $cid )) {
                $str = $customer->getName ();
                $url = $this->getUrl ( 'adminhtml/customer/edit/', array ('id' => $cid, 'rback' => $this->getUrlBase64 ( '*/*/' ) ) );
                $str = '<a href="' . $url . '">' . $str . '</a>';
            }
        }

        return $str;
    }

    /**
     * Render column for export
     *
     * @param Varien_Object $row
     * @return string
    */
    public function renderExport(Varien_Object $row)
    {
        $name = $row->getName();
        return $name;
    }

    /**
     * Tries to load a customer from $this->_customer.
     * If not present, loads a new customer from rewards/customer model
     *
     * @param int $cid
     * @return TBT_Rewards_Model_Customer|bool
    */
    protected function _getCustomer($cid)
    {
        if (isset($this->_customers[$cid])) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer($this->_customers [$cid]);
        }

        $customer = Mage::getModel ( 'rewards/customer' )->load($cid);
        if ($customer->getId ()) {
            $this->_customers[$cid] = $customer;
            return $this->_customers[$cid];
        }

        return false;
    }

}