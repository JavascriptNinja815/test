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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Observer Sales Convert Quote To Order
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Sales_Convert_Quotetoorder implements TBT_Rewards_Model_Customer_Listener
{
    /**
     * Quotation model by reference for quoteToOrder conversions event
     *
     * @var TBT_Rewards_Model_Sales_Quote
     */
    protected $quote = null;
    /**
     * Order model by reference for quoteToOrder conversions event
     *
     * @var TBT_Rewards_Model_Sales_Order
     */
    protected $order = null;

    protected $_reservedIncrementId = null;
    
    protected $_resetDeltas = true;

    public function __construct()
    {
    }

    /**
     * Applies the special price percentage discount
     *
     * @param   Varien_Event_Observer $observer
     * Mage::dispatchEvent('sales_convert_quote_to_order', array('order'=>$order, 'quote'=>$quote));
     *
     * @return  Xyz_Catalog_Model_Price_Observer
     */
    public function prepareCatalogPointsTransfers($observer)
    {
        $event = $observer->getEvent();

        //@nelkaake -a 17/02/11: Save quote info in order

        $this->quote = $event->getQuote();
        $this->order = $event->getOrder();

        if (!$this->quote || !$this->order) {
            return $this;
        }

        $this->order->setRewardsCartDiscountMap($this->quote->getRewardsCartDiscountMap());

        //@nelkaake Added on Thursday May 27, 2010: If mage 1.4 then add "true" tothe checkout method get function
        if (Mage::helper('rewards')->isBaseMageVersionAtLeast('1.4.0.0')) {
            if ($this->quote->getCheckoutMethod(true) == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER) {
                $this->quote->validateQuoteToOrderTransfers();
                //              Mage::helper('rewards')->notice("Checkout method is REGISTER so added customer listener in TBT_Rewards_Model_Observer_Sales_Convert_Quotetoorder...");
                $this->quote->reserveOrderId();
                $this->_reservedIncrementId = $this->quote->getReservedOrderId();
                $this->_getRewardsSession()->addCustomerListener($this);
            } else {
                $this->quote->collectQuoteToOrderTransfers();
            }
        } else {
            if ($this->quote->getCheckoutMethod() == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER) {
                $this->quote->validateQuoteToOrderTransfers();
                //              Mage::helper('rewards')->notice("Checkout method is REGISTER so added customer listener in TBT_Rewards_Model_Observer_Sales_Convert_Quotetoorder...");
                $this->quote->reserveOrderId();
                $this->_reservedIncrementId = $this->quote->getReservedOrderId();
                $this->_getRewardsSession()->addCustomerListener($this);
            } else {
                $this->quote->collectQuoteToOrderTransfers();
            }
        }

        return $this;
    }

    /**
     * Fetches the rewards session
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton('rewards/session');

    }

    /**
     * Triggered when customer model is created
     *
     * @param TBT_Rewards_Model_Customer $customer
     *
     * @return TBT_Rewards_Model_Customer_Listener
     */
    public function onNewCustomerCreate(&$customer)
    {
        //Mage::helper('rewards')->notice("Triggered customer registration listener to generate order catalog points in TBT_Rewards_Model_Observer_Sales_Convert_Quotetoorder.");
        $this->quote->collectQuoteToOrderTransfers(false);

        return $this;
    }
}
