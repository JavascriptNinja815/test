<?php
/**
 * Udevix
 *
 * @author     UdevixTeam <udevix@gmail.com>
 * @copyright  Copyright (c) 2015-2016 Udevix
 */

/**
 * Class Udevix_GoogleTagManager_Helper_Data
 */
class Udevix_GoogleTagManager_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED = 'udevix_gtm/udevix_gtm_group/enable';
    const XML_PATH_CONTAINER_ID = 'udevix_gtm/udevix_gtm_group/container_id';
    const XML_PATH_ENABLE_REMARKETING = 'udevix_gtm/udevix_gtm_remarketing_group/enable_remarketing';
    const XML_PATH_ENABLE_TRANSACTION = 'udevix_gtm/udevix_gtm_transaction_group/enable_transaction';
    const XML_PATH_TRANSACTION_AFFILIATION = 'udevix_gtm/udevix_gtm_transaction_group/transaction_affiliation';
    const XML_PATH_ENABLE_ADWORDS_CONVERSION = 'udevix_gtm/udevix_gtm_transaction_group/enable_adwords_conversion';
    const XML_PATH_ENABLE_PIXEL = 'udevix_gtm/udevix_gtm_transaction_group/enable_pixel';

    /**
     * Return module status
     *
     * @return mixed
     */
    public function isActive()
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED);
    }

    /**
     * Return Google Tag Manager container id
     *
     * @return mixed
     */
    public function getContainerId()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONTAINER_ID);
    }

    /**
     * Return remarketing status
     *
     * @return mixed
     */
    public function isActiveRemarketing()
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLE_REMARKETING);
    }

    /**
     * Return transaction status
     *
     * @return mixed
     */
    public function isActiveTransaction()
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLE_TRANSACTION);
    }

    /**
     * Return transaction affiliation
     *
     * @return string
     */
    public function getTransactionAffiliation()
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTION_AFFILIATION);
    }

    /**
     * Return adwords conversion status
     *
     * @return mixed
     */
    public function isActiveAdwordsConversion()
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLE_ADWORDS_CONVERSION);
    }

    /**
     * Return pixel status
     *
     * @return mixed
     */
    public function isActivePixel()
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLE_PIXEL);
    }
}
