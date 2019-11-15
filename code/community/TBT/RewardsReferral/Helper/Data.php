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
 *
 * @category   [TBT]
 * @package    [TBT_RewardsReferral]
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper Data
 *
 * @category   TBT
 * @package    TBT_RewardsReferral
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Helper_Data extends Mage_Core_Helper_Abstract
{
    const REWARDSREF_URL_STYLE_ID = 1;
    const REWARDSREF_URL_STYLE_EMAIL = 2;
    const REWARDSREF_URL_STYLE_CODE = 3;

    var $_locale;

    protected function getFbLocales()
    {
        return array(
            'af_ZA', 'ar_AR','az_AZ', 'be_BY', 'bg_BG', 'bn_IN', 'bs_BA', 'ca_ES', 'cs_CZ',
            'cy_GB', 'da_DK', 'de_DE', 'el_GR', 'en_GB', 'en_PI', 'en_UD', 'en_US','eo_EO',
            'es_ES', 'es_LA', 'et_EE', 'eu_ES', 'fa_IR', 'fb_LT', 'fi_FI', 'fo_FO', 'fr_CA',
            'fr_FR', 'fy_NL', 'ga_IE', 'gl_ES', 'he_IL', 'hi_IN', 'hr_HR', 'hu_HU', 'hy_AM',
            'id_ID', 'is_IS', 'it_IT', 'ja_JP', 'ka_GE', 'km_KH', 'ko_KR', 'ku_TR', 'la_VA',
            'lt_LT', 'lv_LV', 'mk_MK', 'ml_IN', 'ms_MY', 'nb_NO', 'ne_NP', 'nl_NL', 'nn_NO',
            'pa_IN', 'pl_PL', 'ps_AF', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'sk_SK', 'sl_SI',
            'sq_AL', 'sr_RS', 'sv_SE', 'sw_KE', 'ta_IN', 'te_IN', 'th_TH', 'tl_PH', 'tr_TR',
            'uk_UA', 'vi_VN', 'zh_CN', 'zh_HK', 'zh_TW'
        );
    }

    public function getFacebookLocale()
    {
        if (isset($this->_locale)) {
            return $this->_locale;
        }

        $_locale = Mage::app()->getLocale()->getLocaleCode();

        if (!in_array($_locale, $this->getFbLocales())) {
            $_locale = 'en_US';
        }

        $this->_locale = $_locale;

        return $_locale;
    }

    public function log($msg)
    {
        Mage::log($msg, null, "rewards_referral.log");
        return $this;
    }

    public function notice($msg)
    {
        $this->log("NOTICE: " . $msg);
        return $this;
    }

    //@nelkaake Added on Saturday June 26, 2010:
    public function getReferralUrlStyle()
    {
        return self::REWARDSREF_URL_STYLE_CODE;
    }

    //@nelkaake Added on Saturday June 26, 2010: Same as initateSessionReferral2 but uses customer model instead
    public function initateSessionReferral($newCustomer)
    {
        return $this->initateSessionReferral2($newCustomer->getEmail(), $newCustomer->getName());
    }

    //@nelkaake Added on Saturday June 26, 2010: Same as initateSessionReferral but uses email and name instead.
    public function initateSessionReferral2($newCustomerEmail, $newCustomerName)
    {
        try {
            $referrer = Mage::helper('rewardsref/code')->getReferrer(true);

            if (!$referrer || !$referrer->getId()) {
                return $this;
            }
            
            Mage::getModel('rewardsref/referral')->registerReferral2($referrer, $newCustomerEmail, $newCustomerName);
        } catch (Exception $e) {
            Mage::helper('rewardsref')->log($e->getMessage());
        }
        return $this;
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
     * Get the referral email unsubscription link
     *
     * @param int $customerId
     * @return string (url)
     */
    public function getReferralUnsubscribeUrl($customerId)
    {
        return Mage::getUrl('rewardsref/customer/unsubscribe/') . 'customer/' . base64_encode($customerId);
    }
}
