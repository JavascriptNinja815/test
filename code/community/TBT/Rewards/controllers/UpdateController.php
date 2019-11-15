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
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Update Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */

include_once(Mage::getModuleDir('controllers', 'TBT_Rewards') . DS . 'Front' . DS . 'AbstractController.php');
class TBT_Rewards_UpdateController extends TBT_Rewards_Front_AbstractController
{
    public function indexAction()
    {
        $version = Mage::getResourceSingleton('core/resource')->getDbVersion('rewards_setup');
        if (!$version || version_compare($version, '1.9.0', '<')) {            
            Mage::getSingleton('core/session', array('name'=>'adminhtml'));

            if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
                $authUser = (empty($_SERVER['PHP_AUTH_USER'])) ? null : filter_var($_SERVER['PHP_AUTH_USER']);
                $showAuthenticationBox = true;

                if (isset($authUser)) {
                    $authPassword = (empty($_SERVER['PHP_AUTH_PW'])) ? null : filter_var($_SERVER['PHP_AUTH_PW']);
                    $authResult = Mage::getModel('admin/user')->authenticate($authUser, $authPassword);

                    if ($authResult) {
                        $showAuthenticationBox = false;
                    }
                }

                if ($showAuthenticationBox) {
                    $this->authenticate();
                    return $this;
                }
            }

            if (!Mage::getModel('core/cookie')->get('st_1900_install_confirm')) {
                $blockHtml = Mage::app()->getLayout()
                    ->createBlock('core/template')
                    ->setTemplate('rewards/update.phtml')
                    ->toHtml();

                $this->getResponse()->setBody($blockHtml);
                return $this;
            }
        }
        
        /* Redirect to the last url */
        $savedUrl = Mage::getStoreConfig('rewards/migration/last_url');
        $url = ($savedUrl) ?: '/';
        Mage::app()->getResponse()->setRedirect($url);
        
        Mage::getConfig()->saveConfig('rewards/migration/last_url', null);
        Mage::app()->cleanCache();
        
        return $this;
    }
    
    public function authenticate()
    {
        $helper = Mage::helper('rewards');
        $response = $this->getResponse();
        
        $response->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        $response->setHeader('WWW-Authenticate', 'Basic realm="Store Administrator Log-in"', true);
        $response->setHeader('Status', '401 Unauthorized', true);
        $response->setBody($helper->__('Administrator access is required to run this installation script.'));
        
        return $this;
    }
}

