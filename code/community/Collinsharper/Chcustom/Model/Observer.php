<?php
/**
 * Collinsharper/Custom/Model/Observer.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Chcustom Observer Model
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */

class Collinsharper_Chcustom_Model_Observer
{
    private function _clearBit()
    {
        $this->getCheckoutSession()->unsDisplayRetailCouponError();
    }

    private function _setBit()
    {
        $this->getCheckoutSession()->setDisplayRetailCouponError(true);
    }

    private function getCheckoutSession()
    {
        return Mage:getSingleton('checkout/session');
    }

    public function verifyRetailCoupon($observer)
    {
        $this->_clearBit();
        Varien_Profiler::start(__METHOD__);

        mage::log(__METHOD__ . __LINE__ . " observed");

        $quote = $observer->getEvent()->getQuote();

        if(!$quote || !$this->_getQuote()->getCouponCode()) {
            Varien_Profiler::end(__METHOD__);
            return $this;
        }

        $rule = Mage::getModel('salesrule/rule')->loadByAttribute('coupon_code', $this->_getQuote()->getCouponCode());

        if(!$rule || !$rule->getMyretail()) {
            Varien_Profiler::end(__METHOD__);
            return $this;
        }

        $items = $quote->getAllItems();
        $found = false;
        foreach($items as $item) {
            if($item->getSku() == Collinsharper_Chcustom_Model_Cron::RETAIL_PACKAGING_SKU) {
                $found = true;
                break;
            }
        }

        if(!$found) {
            // add errormessage for the thingy
            $this->_setBit();
        }

        Varien_Profiler::end(__METHOD__);
    }
}