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
 * Helper Data
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Does the store have any rules?
     *
     * @var bool
     */
    protected $rulesExist;

    /**
     * Return points string using TBT_Rewards_Block_Points
     *
     * Note: at one point this returned a TBT_Rewards_Block_Points object
     * This has been revised to force the return type as string
     *
     * @param array|int $points_array
     * @return string
     */
    public function getPointsString($points_array, $formatPoints = false, $pointsNumberFormat = true)
    {
        if (!is_array($points_array)) {
            $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            $points_array = array($defaultCurrencyId => $points_array);
        }
        
        $pointsString = (string)Mage::getModel('rewards/points')->add($points_array)
            ->setFormatPoints($formatPoints)
            ->getRendering();

        if ($pointsNumberFormat) {
            $pointsNumber = (int) $pointsString;
            $pointsString = str_replace(
                $pointsNumber, 
                $this->formatNumberByLocale($pointsNumber),
                $pointsString
            );
        }
        
        return $pointsString;
    }
    
    /**
     * Adds . and , as separators for thousands and decimals based on current locale
     * @param int|float $val
     * @return string
     */
    public function formatNumberByLocale($val)
    {
        return Zend_Locale_Format::toNumber(
            $val, 
            array(
                'locale' => new Zend_Locale(Mage::app()->getLocale()->getLocale())
            )
        );
    }

    /**
     * Code will be in the format [-]##[%]
     * [-] will subtract the value from the product,
     * no [-] will make the value of the price equal to the number.
     * The [%] makes the number a percent of the price
     *
     * -10% reduces the price by 10%
     * 30% makes the price 30% of the original
     * 5 makes the price 5
     * -15 reduce the price by 15
     *
     * @param int            $price
     * @param string         $code
     * @param        boolean $reverse_currency Reverse the current store currency before making the calculation?
     * @param        boolean $round_price      Round the price after making the calculation?
     *
     * @nelkaake 15/01/2010 6:45:20 PM : added $round_price flag to NOT round the price as in some cases needed
     * @see      Block\Product\View\Points.php
     *
     * @throws Exception
     *
     * @return int $price
     */
    public function priceAdjuster($price, $code, $reverse_currency = true, $round_price = true)
    {
        if (strpos($code, "-") !== false) {

            //Depending on the effect, it modifies a temp price to compare to
            if (strpos($code, "%") !== false) {
                $fx    = ( float )(1 + str_replace("%", "", $code) / 100);
                $price = $price * $fx;
            } else {
                $fx = ( float )$code;
                if ($reverse_currency) {
                    $fx = ( float )$this->_getAggregatedCart()->getStore()->convertPrice(( float )$code);
                }
                $price = $price + $fx;
            }
        } else {
            if (strpos($code, "%") !== false) {
                $fx    = ( float )(str_replace("%", "", $code) / 100);
                $price = $price * $fx;
            } else {
                $fx = ( float )$code;
                if ($reverse_currency) {
                    $fx = ( float )$this->_getAggregatedCart()->getStore()->convertPrice(( float )$code);
                }
                $price = $fx;
            }
        }
        $price = $this->_getAggregatedCart()->getStore()->roundPrice($price);

        return $price;
    }

    /**
     * Adjusts the price using priceAdjuster but mutliple times
     * For exmaple if you need to add 5 x 10% discount, set the last
     * paramter to 5.
     * @throws Exception
     * @see priceAdjuster($price, $code)
     *
     * @param int     $price
     * @param string  $code
     * @param integer $uses must be greater than 0
     *
     * @return float $price
     */
    public function priceAdjusterMulti($price, $code, $uses)
    {
        if (( int )$uses <= 0) {
            return $price;
        }
        $new_price         = $this->priceAdjuster($price, $code);
        $price_disposition = $price - $new_price;
        $final_price       = $price - ($price_disposition * $uses);
        if ($final_price < 0) {
            $final_price = 0;
        }

        return $final_price;
    }

    /**
     * Applifies an effect up to maximum of a product price or -100%
     * @throws Exception
     * @see priceAdjuster($price, $code)
     *
     * @param int     $price
     * @param string  $effect_code
     * @param integer $uses must be greater than 0
     *
     * @return string $price new effect
     */
    public function amplifyEffect($price, $effect_code, $uses)
    {
        $old_effect = $effect_code;
        if (strpos($old_effect, "-") !== false) {

            //Depending on the effect, it modifies a temp price to compare to
            if (strpos($old_effect, "%") !== false) {
                $new_effect = $uses * (( float )str_replace("%", "", $old_effect));
                if ($new_effect < -100) {
                    $new_effect = -100;
                }
                $new_effect = $new_effect . "%";
            } else {
                $new_effect = $uses * $old_effect;
                if ($new_effect * -1 > $price) {
                    $new_effect = $price * -1;
                }
            }
        } else { // YOU CAN'T AMPLIFY "% OF PRODUCT PRICE" DISCOUNTS
            $new_effect = $old_effect;
        }

        return $new_effect;
    }

    /**
     * Check is module exists and enabled in global config.
     *
     * @param string $moduleName the full module name, example Mage_Core
     * @return boolean
     */
    public function isModuleEnabled($moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = $this->_getModuleName();
        }

        if (!Mage::getConfig()->getNode('modules/' . $moduleName)) {
            return false;
        }

        $isActive = Mage::getConfig()->getNode('modules/' . $moduleName . '/active');
        if (!$isActive || !in_array((string)$isActive, array('true', '1'))) {
            return false;
        }
        return true;
    }

    /**
     * Performs a base64_encode and json_encode on
     * a variable then returns the result.
     *
     * @param mixed $arr
     *
     * @return string
     */
    public function hashIt($value)
    {
        if (is_null($value)) {
            $value = array();
        }

        return base64_encode(json_encode($value));
    }

    /**
     * Performs a base64_decode and json_decode on
     * a variable then returns the result.
     *
     * @param mixed $arr
     *
     * @return array
     */
    public function unhashIt($value)
    {
        if (is_null($value)) {
            return array();
        }
        $unhashed = json_decode(base64_decode($value));
        $unhashed = ( array )$unhashed;

        return $unhashed;
    }

    /**
     * Takes in a points string and wraps  the "#_xyz" portion(s) in bold tags..
     *
     * IE: "12 A Points, 1 Zee point and maybe even your 90 xyZ points"
     * would become "<b>12 A</b> Points, <b>1 Zee</b> point and maybe even your <b>90 xyZ</b> points"
     *
     * @param unknown_type $points_str
     *
     * @return unknown
     */
    /*
    @kadukia, removed class points-summary-emphasis, instead added this manually in summary.php to bold entire currency name on June 20
    */
    public function emphasizeThePoints($points_str)
    {
        $new_points_str = preg_replace("([0-9,]+ [a-zA-Z]+)", '$0', $points_str);

        return $new_points_str;
    }

    /**
     * Get store timestamp
     * Timstamp will be builded with store timezone settings
     *
     * @param   mixed $store
     *
     * @return  int
     */
    public function storeTimeStamp($store = null)
    {
        if ($this->isBaseMageVersionAtLeast('1.3.0.0')) {
            return Mage::app()->getLocale()->storeTimeStamp($store);
        }

        //$timezone = Mage::app()->getStore($store)->getConfig(self::XML_PATH_DEFAULT_TIMEZONE);
        $currentTimezone = @date_default_timezone_get();
        //@date_default_timezone_set($timezone);
        $date = date('Y-m-d H:i:s');
        @date_default_timezone_set($currentTimezone);

        return strtotime($date);
    }

    public function getIsMilestoneEnabled()
    {
        return Mage::getConfig()->getModuleConfig('TBT_Milestone')->is('active', 'true');
    }

    /**
     * Returns true if the page controller is multiship or if we are in the cart controller
     *
     * @param $quote = null   This will either be a quote model or a address model
     *
     * @return boolean
     */
    public function isMultishipMode($quote = null)
    {
        if ($quote == null) {
            $quote = $this->getRS()->getQuote();
        }
        $quote_is_multiship    = $quote->getIsMultiShipping();
        $page_is_cart          = ($this->_getRequest()->getControllerName() == 'cart');
        $page_is_multishipping = ($this->_getRequest()->getControllerName() == 'multishipping');

        return ($quote_is_multiship && !$page_is_cart) || $page_is_multishipping;
    }

    /**
     * Returns true if the page controller is multiship or if we are in the cart controller
     *
     * @param $quote = null   This will either be a quote model or a address model
     *
     * @return boolean
     */
    public function isMultishipingCheckout($quote = null)
    {
        if ($quote == null) {
            $quote = $this->getRS()->getQuote();
        }
        $page_is_multishipping = ($this->_getRequest()->getControllerName() == 'multishipping');

        return $page_is_multishipping;
    }

    /**
     * Checks if we are in Admin (back-end) section.
     * @return boolean Returns true if we are in admin, false otherwise
     */
    public function getIsAdmin()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return true;
        }

        if (Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }

        return false;
    }

    /**
     * Fetches the rewards session.
     *
     * @return TBT_Rewards_Model_Session
     */
    public function getRS()
    {
        return Mage::getSingleton('rewards/session');
    }

    /**
     * True if the current page path matches the specified
     * page path.
     *
     * @param string $path : ie rewards/customer/view
     *
     * @return boolean
     */
    public function isCurrentPage($path)
    {
        $current_module     = $this->_getRequest()->getModuleName();
        $current_controller = $this->_getRequest()->getControllerName();
        $current_section    = $this->_getRequest()->getActionName();
        $current_path       = $current_module . "/" . $current_controller . "/" . $current_section;

        return ($path == $current_path);
    }

    /**
     * Fetches the current date in the format 'Y-m-d'
     * and based on the currently loaded store.
     * @nelkaake Changed on Wednesday September 22, 2010: moved to Datetime helper
     * @return string
     */
    public function now($dayOnly = true)
    {
        return Mage::helper('rewards/datetime')->now($dayOnly);
    }

    // @nelkaake Changed on Wednesday September 22, 2010: moved to Datetime helper
    public function getCurrentFromDate()
    {
        return Mage::helper('rewards/datetime')->getCurrentFromDate();
    }

    // @nelkaake Changed on Wednesday September 22, 2010: moved to Datetime helper
    public function getCurrentToDate()
    {
        return Mage::helper('rewards/datetime')->getCurrentToDate();
    }


    /**
     * @nelkaake Added on Thursday May 27, 2010: Logging method for ST functions
     *
     * @param mixed $msg
     */
    public function log($msg)
    {
        return Mage::helper('rewards/debug')->log($msg);
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Notice-level logging function
     *
     * @param mixed $msg
     */
    public function notice($msg)
    {
        return Mage::helper('rewards/debug')->notice($msg);
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Notice-level logging function
     *
     * @param mixed $msg
     */
    public function warn($msg)
    {
        return Mage::helper('rewards/debug')->warn($msg);
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Notice-level logging function
     *
     * @param mixed $msg
     */
    public function error($msg)
    {
        return Mage::helper('rewards/debug')->error($msg);
    }

    /**
     * Logs an exception into the Sweet TOoth log file
     *
     * @param Exception $msg
     */
    public function logException($e)
    {
        return Mage::helper('rewards/debug')->logException($e);
    }


    /**
     * Will accept an array of Models or a single model,
     * and return a json object representing the object
     *
     * @param Mage_Core_Model_Abstract|array<Mage_Core_Model_Abstract> $object
     * @param bool $isIntermediateStep, used for recursive calls (leave as false if calling externally)
     * @return string, the final json
     */
    public function toJson($object, $isIntermediateStep = false)
    {
        if (empty($object)) return "{}";

        if ($object instanceof Varien_Data_Collection_Db){
            $object = $object->getItems();
        }

        if (is_array($object)) {
            foreach ($object as $index => $item) {
                if ($item instanceof Mage_Core_Model_Abstract) {
                    $object[$index] = $item->getData();
                }

                if (is_array($item)) {
                    $object[$index] = $this->toJson($item, true);
                }
            }

            if ($isIntermediateStep) {
                // Used for recursive calls
                return $object;
            }

        } else if ($object instanceof Mage_Core_Model_Abstract) {
            $object = $object->getData();
        }

        return json_encode($this->_toUTF8($object));
    }


    /**
     * Will convert characters inside elements of an array into UTF-8 format
     * @param $array
     * @return array
     */
    protected function _toUTF8($array)
    {
        array_walk_recursive($array, function(&$item, $key){
            if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
            }
        });

        return $array;
    }

    /**
     * start E_DEPRECATED =================================================================================
     */
    public function isMageVersion12()
    {
        return Mage::helper('rewards/version')->isMageVersion12();
    }

    public function isMageVersion131()
    {
        return Mage::helper('rewards/version')->isMageVersion131();
    }

    public function isMageVersion14()
    {
        return Mage::helper('rewards/version')->isMageVersion14();
    }

    public function isMageVersionAtLeast14()
    {
        return Mage::helper('rewards/version')->isMageVersionAtLeast14();
    }

    public function convertVersionToCommunityVersion($version, $task = null)
    {
        return Mage::helper('rewards/version')->convertVersionToCommunityVersion($version, $task);
    }

    /**
     * @deprecated use rewards/version helper
     */
    public function isBaseMageVersionAtLeast($version, $task = null)
    {
        return Mage::helper('rewards/version')->isBaseMageVersionAtLeast($version, $task);
    }

    /**
     * @deprecated use rewards/version helper
     */
    public function isBaseMageVersion($version, $task = null)
    {
        return Mage::helper('rewards/version')->isBaseMageVersion($version, $task);
    }

    public function isMageVersionAtLeast($version, $task = null)
    {
        return Mage::helper('rewards/version')->isMageVersionAtLeast($version, $task);
    }
    /**
     * end E_DEPRECATED =================================================================================
     */

    /**
     * Check if the store has any reward rules
     * @return bool
     */
    public function storeHasRewardRules()
    {
        if (is_null($this->rulesExist)) {
            $cartRulesCollection = Mage::getModel('rewards/salesrule_rule')
                ->getResourceCollection()
                ->addFieldToFilter("points_action", array('neq' => ''));

            $specialRulesCollection = Mage::getModel('rewards/special')
                ->getResourceCollection();

            $this->rulesExist = (
                $cartRulesCollection->getSize() > 0 
                || $specialRulesCollection->getSize() > 0
            );
        }
        
        return $this->rulesExist;
    }

    /**
     * Format price based on aggregated store
     * @param string|decimal $price
     * @return string
     */
    public function formatPriceForAllAreas($price)
    {
        return $this->_getAggregatedCart()->getStore()->formatPrice($price);
    }

    /**
     * Aggregation Cart instance
     * @return TBT_Rewards_Model_Sales_Aggregated_Cart
     */
    protected function _getAggregatedCart()
    {
        return Mage::getSingleton('rewards/sales_aggregated_cart');
    }
    
    /**
     * get Sweet Tooth version
     * @return string
     */
    public function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getNode('modules/TBT_Rewards/version');
    }

    /**
     * Helper method used in layout for enabling or disabling rewards cart discounts full summary
     * @return string
     */
    public function removeDiscountRenderer()
    {
        $config = Mage::helper('rewards/config')->displayCartDiscountFullSummary();

        if (!$config) {
            return 'discount';
        }

        return 'tbt_rewards_discount_renderer_block_that_does_not_exist';
    }
}
