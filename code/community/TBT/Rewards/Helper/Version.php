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
 * Version Helper Data
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Version extends Mage_Core_Helper_Abstract
{

    /**
     * Returns true if the base version of this Magento installation
     * is equal to the version specified or newer.
     * @param string $version
     * @param unknown_type $task
     */
    public function isBaseMageVersionAtLeast($version, $task = null)
    {
        // convert Magento Enterprise, Professional, Community to a base version
        $mage_base_version = $this->convertVersionToCommunityVersion(Mage::getVersion(), $task);

        if (version_compare($mage_base_version, $version, '>=')) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns true if the base version of this Magento installation
     * is equal to the version specified or older.
     * @param string $version
     * @param unknown_type $task
     */
    public function isBaseMageVersionAtMost($version, $task = null)
    {
        // convert Magento Enterprise, Professional, Community to a base version
        $mage_base_version = $this->convertVersionToCommunityVersion(Mage::getVersion(), $task);
        $version = $this->convertAnyVersionToCommunityVersion($version, $task);
        
        if (version_compare($mage_base_version, $version, '<=')) {
            return true;
        }
        return false;
    }

    /**
     * True if the base version is at least the verison specified without converting version numbers to other versions
     * of Magento.
     *
     * @param string $version
     * @param unknown_type $task
     * @return boolean
     */
    public function isRawVerAtLeast($version)
    {
        // convert Magento Enterprise, Professional, Community to a base version
        $mage_base_version = Mage::getVersion ();

        if (version_compare($mage_base_version, $version, '>=')) {
            return true;
        }

        return false;
    }

    /**
     * True if the base version is at least the verison specified without checking
     * @param string $version
     */
    public function isEnterpriseAtLeast($version)
    {
        if (!$this->isMageEnterprise()) {
            return false;
        }

        return $this->isRawVerAtLeast($version);
    }

    /**
     *
     * @param string $version
     * @param unknown_type $task
     * @return boolean
     */
    public function isBaseMageVersion($version, $task = null)
    {
        // convert Magento Enterprise, Professional, Community to a base version
        $mage_base_version = $this->convertVersionToCommunityVersion(Mage::getVersion (), $task);
        
        if (version_compare($mage_base_version, $version, '=')) {
            return true;
        }

        return false;
    }

    /**     * @alias isBaseMageVersion     */
    public function isMageVersion($version, $task = null)
    {
        return $this->isBaseMageVersion ( $version, $task );
    }

    /**     * @alias isBaseMageVersion     */
    public function isMage($version, $task = null)
    {
        return $this->isBaseMageVersion ( $version, $task );
    }

    /**     * @alias isBaseMageVersionAtLeast     */
    public function isMageVersionAtLeast($version, $task = null)
    {
        return $this->isBaseMageVersionAtLeast ( $version, $task );
    }

    /**
     * True if the Magento version currently running is between the versions specified inclusive
     * @nelkaake -a 16/11/10:
     * @param string $version
     * @param unknown_type $task
     * @return boolean
     */
    public function isMageVersionBetween($version1, $version2, $task = null)
    {
        $is_between = $this->isBaseMageVersionAtLeast($version1, $task)
            && !$this->isBaseMageVersionAtLeast($version2, $task);
        $is_later_version = $this->isMageVersion($version2);

        return $is_between || $is_later_version;
    }

    /**
     * True if the version of Magento currently being run is Enterprise Edition
     */
    public function isMageEnterprise()
    {
        $isMageEnterprise =  Mage::getConfig()->getModuleConfig('Enterprise_Enterprise')
            && Mage::getConfig()->getModuleConfig('Enterprise_AdminGws')
            && Mage::getConfig()->getModuleConfig('Enterprise_Checkout')
            && Mage::getConfig()->getModuleConfig('Enterprise_Customer');

        return $isMageEnterprise;
    }

    /**
     * attempt to convert an Enterprise, Professional, Community magento version number to its compatable Community version
     *
     * @param string $task fix problems where direct version numbers cant be changed to a community release without knowing the intent of the task
     */
    public function convertVersionToCommunityVersion($version, $task = null)
    {
        /* Enterprise -
         * 1.9 | 1.8 | 1.5
         */
        if ($this->isMageEnterprise()) {
            if (version_compare ( $version, '1.14.2.0', '>=' )) {
                return '1.9.2.0';
            }
            if (version_compare ( $version, '1.14.1.0', '>=' )) {
                return '1.9.1.0';
            }
            if (version_compare ( $version, '1.14.0.1', '>=' )) {
                return '1.9.0.1';
            }
            if (version_compare ( $version, '1.14.0.0', '>=' )) {
                return '1.9.0.0';
            }
            if (version_compare ( $version, '1.13.1.0', '>=' )) {
                return '1.8.1.0';
            }
            if (version_compare ( $version, '1.13.0.0', '>=' )) {
                return '1.8.0.0';
            }
            if (version_compare ( $version, '1.12.0.2', '>=' )) {
                return '1.7.0.2';
            }
            if (version_compare ( $version, '1.12.0.1', '>=' )) {
                return '1.7.0.1';
            }
            if (version_compare ( $version, '1.12.0.0', '>=' )) {
                return '1.7.0.0';
            }
            if (version_compare ( $version, '1.11.2.0', '>=' )) {
                return '1.6.2.0';
            }
            if (version_compare ( $version, '1.11.1.0', '>=' )) {
                return '1.6.1.0';
            }
            if (version_compare ( $version, '1.11.0.0', '>=' )) {
                return '1.6.0.0';
            }
            if (version_compare ( $version, '1.9.1.0', '>=' )) {
                return '1.5.0.0';
            }
            if (version_compare ( $version, '1.9.0.0', '>=' )) {
                return '1.4.2.0';
            }
            if (version_compare ( $version, '1.8.0.0', '>=' )) {
                return '1.3.1.0';
            }

            return '1.3.1.0';
        }

        /* Professional -
         * If Entprise_Enterprise module is installed but it didn't pass Enterprise_Enterprise tests
         * then the installation must be Magento Pro edition.
         * 1.7 | 1.8
         */
        if (Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' )) {
            if (version_compare ( $version, '1.11.0.0', '>=' ))
                return '1.6.0.0';
            if (version_compare ( $version, '1.10.0.0', '>=' ))
                return '1.5.0.0';
            if (version_compare ( $version, '1.9.0.0', '>=' ))
                return '1.4.1.0';
            if (version_compare ( $version, '1.8.0.0', '>=' ))
                return '1.4.1.0';
            if (version_compare ( $version, '1.7.0.0', '>=' ))
                return '1.3.1.0';
            return '1.3.1.0';
        }

        /* Community -
         * 1.5rc2 - December 29, 2010
         * 1.4.2 - December 8, 2010
         * 1.4.1 - June 10, 2010
         * 1.3.3.0 - (April 23, 2010) *** does this release work like to 1.4.0.1?
         * 1.4.0.1 - (February 19, 2010)
         * 1.4.0.0 - (February 12, 2010)
         * 1.3.0 - March 30, 2009
         * 1.2.1.1 - February 23, 2009
         * 1.1 - July 24, 2008
         * 0.6.1316 - October 18, 2007
         */
        return $version;
    }
    
    /**
     * attempt to convert an Enterprise, Professional, Community magento version number to its compatable Community version
     *
     * @param string $task fix problems where direct version numbers cant be changed to a community release without knowing the intent of the task
     */
    public function convertAnyVersionToCommunityVersion($version, $task = null)
    {
        if (version_compare ( $version, '1.14.2.0', '>=' )) {
            return '1.9.2.0';
        }
        if (version_compare ( $version, '1.14.1.0', '>=' )) {
            return '1.9.1.0';
        }
        if (version_compare ( $version, '1.14.0.1', '>=' )) {
            return '1.9.0.1';
        }
        if (version_compare ( $version, '1.14.0.0', '>=' )) {
            return '1.9.0.0';
        }
        if (version_compare ( $version, '1.13.1.0', '>=' )) {
            return '1.8.1.0';
        }
        if (version_compare ( $version, '1.13.0.0', '>=' )) {
            return '1.8.0.0';
        }
        if (version_compare ( $version, '1.12.0.0', '>=' )) {
            return '1.7.0.0';
        }
        if (version_compare ( $version, '1.11.0.0', '>=' )) {
            return '1.6.0.0';
        }
        if (version_compare ( $version, '1.10.0.0', '>=' )) {
            return '1.5.0.0';
        }

        return $version;
    }

    /**
     * Convert EE to CE version
     * @param string $version
     * @return string
     */
    public function convertAnyVersionToCommunity($version)
    {
        /**
         * Return if already community
         * Skip check for professional version and other EE old versions that are deprecated
         */
        if (version_compare($version, '1.10.0.0', '<' )) {
            return $version;
        }

        $tokenizedVersion = explode('.', $version);

        if (isset($tokenizedVersion[1])) {
            $tokenizedVersion[1] = $tokenizedVersion[1] - 5;
        }

        return implode('.', $tokenizedVersion);
    }

    /**
     * Check if current mage version is in version range
     * This will automatically convert any EE to CE equivalent version
     * @param string $version1
     * @param string $version2
     * @return boolean
     */
    public function isBaseMageVersionBetween($version1, $version2)
    {
        $mageVersion = $this->convertAnyVersionToCommunity(Mage::getVersion());
        $version1 = $this->convertAnyVersionToCommunity($version1);
        $version2 = $this->convertAnyVersionToCommunity($version2);

        if (version_compare($mageVersion, $version1, '<' )) {
            return false;
        }

        if (version_compare($mageVersion, $version2, '>' )) {
            return false;
        }

        return true;
    }


    /**
     * start E_DEPRECATED =================================================================================
     */
    /**
     * @deprecated use isBaseMageVersion isntead
     * @return boolean
     */
    public function isMageVersion12() {
        return $this->isMageVersion ( '1.2' );
    }

    /**
     * @deprecated use isBaseMageVersion isntead
     * @return boolean
     */
    public function isMageVersion131() {
        return $this->isMageVersion ( '1.3.1' );
    }

    /**
     * @deprecated use isBaseMageVersion instead
     * @return boolean
     */
    public function isMageVersion14() {
        return $this->isMageVersion ( '1.4' );
    }

    /**
     * @deprecated use isMageVersionAtLeast isntead
     * @return boolean
     */
    public function isMageVersionAtLeast14() {
        //@nelkaake Changed on Sunday August 15, 2010:
        return $this->isBaseMageVersionAtLeast ( '1.4.0.0' );
    }

/**
 * end E_DEPRECATED =================================================================================
 */
}
