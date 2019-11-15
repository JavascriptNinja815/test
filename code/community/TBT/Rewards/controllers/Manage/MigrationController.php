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
 * Customer Send Points Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Manage_MigrationController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }

    /**
     * @deprecated
     */
    private $total_migrated = 0;
    private $error_list;
    private $site_id;

    /**
     * Exports all Sweet Tooth rules from Magento.
     */
    public function exportCampaignAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/txt', true);
        $ts = Mage::app()->getLocale()->storeTimeStamp();
        $filename = "rules_{$ts}.stcampaign";
        $this->getResponse()->setHeader('Content-Disposition', "inline; filename={$filename}");
        
        $soutput = Mage::getModel('rewards/migration_export')->getSerializedCampaignExport();
        $this->getResponse()->setBody($soutput);
    }

    /**
     * Deletes all Sweet Tooth rules from Magento.
     *
     * @return self
     */
    public function deleteCampaignAction()
    {
        $backup = null;
        
        try {
            $backup = Mage::getSingleton('rewards/migration_export')->getSerializedCampaignExport();
            Mage::getSingleton('rewards/migration_delete')->deleteOnlyRules();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('All MageRewards rules successfully deleted.')
            );
        } catch (Exception $e) {
            Mage::getSingleton('rewards/migration_logger')->log($e);
            
            if (isset($backup)) {
                Mage::getSingleton('rewards/migration_import')->importFromSerializedData($backup);
            }

            throw $e;
        }

        $this->_redirectReferer();
    }

    /**
     * Reset Sweet Tooth settings to the default ones, for current admin configuration scope.
     * Note: the default scope will reset all Sweet Tooth configuration settings across Magento.
     *
     * @return self
     */
    public function resetSettingsAction()
    {
        try {
            $params = $this->getRequest()->getParams();
            $helper = Mage::helper('rewards/migration');

            Mage::getSingleton('rewards/migration_delete')
                ->deleteAllRewardsConfig();

            $helper->createAdminNotification('reset', $params);
            Mage::app()->getCacheInstance()->flush();
        } catch (Exception $e) {
            Mage::getSingleton('rewards/migration_logger')->log($e);
            throw $e;
        }

        $this->_redirectReferer();
    }

    /**
     * Exports Sweet Tooth settings, for current admin configuration scope. File's name depends on the System
     * Configuration Scope selected, always having '.stsettings' extension.
     *     - if it's a store scope: websiteCode_storeCode_timestamp.stsettings
     *     - if it's a website scope: websiteCode_timestamp.stsettings
     *     - if it's default scope: default_timestamp.stsettings
     *
     * Note: the default scope will export all Sweet Tooth configuration settings across Magento.
     */
    public function exportSettingsAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/txt', true);

        $params    = $this->getRequest()->getParams();
        $timeStamp = Mage::app()->getLocale()->storeTimeStamp();
        if (isset($params['store'])) {
            $filename = $params['website'] . '_' . $params['store'];
        } elseif (isset($params['website'])) {
            $filename = $params['website'];
        } else {
            $filename = "default";
        }
        $filename .= '_' . $timeStamp . ".stsettings";

        $this->getResponse()->setHeader('Content-Disposition', "inline; filename={$filename}");
        $soutput = Mage::getModel('rewards/migration_export')->getSerializedConfigExport();
        $this->getResponse()->setBody($soutput);
    }

    /**
     * @deprecated
     * @see resetSettingsAction()
     */
    public function revertconfigAction()
    {
        $this->resetSettingsAction();
    }

    /**
     * @deprecated
     * @see exportSettingsAction()
     */
    public function exportconfigAction()
    {
        $this->exportSettingsAction();
    }

    /**
     * @deprecated
     * @see  self::deleteCampaignAction() & self::resetSettingsAction()
     */
    public function deleteallAction()
    {
        try {
            $backup = Mage::getSingleton('rewards/migration_export')->getSerializedFullExport();
            Mage::getSingleton('rewards/migration_delete')->deleteAll();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('All rules and settings were deleted.')
            );
        } catch (Exception $e) {
            Mage::getSingleton('rewards/migration_logger')->log($e);
            if (isset($backup)) {
                Mage::getSingleton('rewards/migration_import')->importFromSerializedData($backup);
            }

            throw $e;
        }
        $this->_redirectReferer();
    }

    /**
     * @deprecated
     * @see  self::exportCampaignAction() & self::exportSettingsAction()
     */
    public function exportallAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/txt');
        $ts = Mage::app()->getLocale()->storeTimeStamp();
        $filename = "rules_and_config_{$ts}.stcampaign";
        $this->getResponse()->setHeader('Content-Disposition', "inline; filename={$filename}");
        $soutput = Mage::getModel('rewards/migration_export')->getSerializedFullExport();
        $this->getResponse()->setBody($soutput);
    }

    /**
     * @deprecated  not used anywhere
     */
    public function migrateAction()
    {
        Mage::getModel('rewards/transfer')->getResource()->beginTransaction();

        $this->total_migrated = 0;
        $this->error_list     = array();

        $server        = $this->getRequest()->get('server');
        $database      = $this->getRequest()->get('database');
        $user          = $this->getRequest()->get('user');
        $password      = $this->getRequest()->get('pass');
        $this->site_id = $this->getRequest()->get('site');

        $db = new mysqli ($server, $user, $password, $database);
        if ($db->connect_error) {
            $this->getResponse()->setBody("There was an error connecting with the database");
            return $this;
        }
        
        $content = "Connected to database...<br>";
        $sql_query = 'SELECT customers_email_address,
            customers_shopping_points
            FROM customers
            WHERE customers_shopping_points != 0';

        $content .= "Running query...<br>";
        $result = $db->query($sql_query);

        while ($row = $result->fetch_row()) {
            if (isset ($migration_list [$row [0]])) {
                $this->error_list [] = "ERROR: " . $cust_email . " is a duplicate email<br>";
            }
            $migration_list [$row [0]] = $row [1];
            $this->createTransfer($row [0], $row [1], $site_id);
        }

        $content .= "<br><br> MIGRATION COMPLETE: ";
        $content .= $this->total_migrated . " customer point balances were migrated over<br><br>";

        $content .= "ERRORS:";
        if (count($this->error_list) == 0) {
            $content .= " No errors.";
        } else {
            $content .= "<br>";
            foreach ($this->error_list as $error) {
                $content .= $error;
            }
        }
        
        $result->close();
        $db->close();
        Mage::getModel('rewards/transfer')->getResource()->commit();
        $this->getResponse()->setBody($content);
    }

    /**
     * @deprecated  not used anywhere
     */
    private function createTransfer($cust_email, $num_points)
    {

        $cust = Mage::getModel('rewards/customer')->setWebsiteId($this->site_id)->loadByEmail($cust_email);
        if (!$cust) {
            Mage::helper('rewards/debug')->printMessage("ERROR: " . $cust_email . " can not be loaded<br>");
            $this->error_list [] = "ERROR: " . $cust_email . " can not be loaded<br>";
        } else {
            Mage::helper('rewards/debug')->printMessage("MIGRATED: " . $cust_email . " now has " . $num_points . " points<br>");
            $this->total_migrated++;
            $transfer = Mage::getModel('rewards/transfer');

            $transfer->setId(null)
                ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('adjustment'))
                ->setCustomerId($cust->getId())->setQuantity(round($num_points))->setStatusId(5)
                ->setComments("Migrated from old database")
                ->save();
        }
    }
}
