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
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Quick Launch Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */

class TBT_Rewards_QuickLaunchController extends Mage_Adminhtml_Controller_Action 
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    public function indexAction() 
    {
        if (Mage::helper('rewards')->storeHasRewardRules()) {
            $this->_redirect('adminhtml/rewardsDashboard/index');
        }
        
        $helper = Mage::helper('rewards/quickLaunch');
        $data = $this->getRequest()->getParams();

        // Execute step actions
        $helper->executeAction($data);

        // Check for last step
        $step = (isset($data['step'])) ? $data['step'] : null;
        if ($helper->isLastStep($step)) {
            Mage::getConfig()->saveConfig('rewards/general/last_quick_launch', Mage::helper('rewards/datetime')->now(false, true));
            return $this->_redirect('adminhtml/quickLaunch/success');
        }

        if (!empty($data['password'])) {
            return $this->_redirect('adminhtml/quickLaunch/index', array('_query' => "step={$data['step']}"));
        }
        
        $this->loadLayout();

        // Assign step to quick launch block
        $nextStep = $helper->getNextStep($step);
        Mage::app()->getLayout()->getBlock('quickLaunch')->setStep($nextStep);

        $this->renderLayout();
    }
    
    public function resetSettingsAction()
    {
        Mage::getConfig()->saveConfig('rewards/quickLaunch/loyaltyProgramData', null);
        Mage::getConfig()->cleanCache();
        
        $this->_redirect('adminhtml/quickLaunch/index');
    }
    
    public function successAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function explainerAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function launchExplainerAction()
    {
        $helper = Mage::helper('rewards');
        $data = Mage::getStoreConfig('rewards/quickLaunch/loyaltyProgramData');
        $data = Mage::helper('rewards/serializer')->unserializeData($data);
        
        if (empty($data)) {
            $message = $helper->__('We don\'t have enough data to create your explainer page');
            Mage::getSingleton('adminhtml/session')->addError($message);
            return $this->_redirect('adminhtml/rewardsDashboard/index');
        }
        
        $programName = (empty($data['program-name'])) ? $helper->__('MageRewards Program') : $data['program-name'];
        $identifier = str_replace(' ', '-', strtolower($programName));
        $cmsPage = Mage::getModel('cms/page')->load($identifier, 'identifier');
        
        if ($cmsPage && $cmsPage->getId()) {
            $message = $helper->__('Looks like you already have an explainer page, so we loaded the existing one.');
            Mage::getSingleton('adminhtml/session')->addNotice($message);
            return $this->_redirect('adminhtml/cms_page/edit', array('page_id' => $cmsPage->getId()));
        }
        
        $theme = $this->getRequest()->getParam('theme'); 
        if (!$theme) {
            $theme = TBT_Rewards_Block_QuickLaunch::DEFAULT_THEME;
        }
        
        $html = $this->getLayout()
            ->createBlock('rewards/quickLaunch')
            ->setTemplate('rewards/quickLaunch/explainer/content.phtml')
            ->setColor($theme)
            ->toHtml();

        $pageData = array(
            'title' => $programName,
            'root_template' => 'one_column',
            'identifier' => $identifier,
            'stores' => array(0), /* available for all store views */
            'content' => $html,
            'layout_update_xml' => '
                <reference name="head">
                    <block type="core/text" name="render-async-content" output="toHtml">
                        <action method="setText">
                            <text>
                                <![CDATA[<script type="text/javascript">
                                    (function() {
                                        var firstTag = document.getElementsByTagName(\'script\')[0];
                                        var links = [
                                            "https://fonts.googleapis.com/css?family=Lato",
                                            "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"
                                        ];

                                        links.forEach(function(url) {
                                            var externalCss = document.createElement(\'link\'); 
                                            externalCss.rel = \'stylesheet\';
                                            externalCss.href = url;
                                            firstTag.parentNode.insertBefore(externalCss, firstTag);
                                        });
                                    })();
                                </script>]]>
                            </text>
                        </action>
                    </block>
                    <action method="addCss"><stylesheet>css/rewards/explainer.css</stylesheet></action>
                </reference>'
        );

        $newCmsPage = Mage::getModel('cms/page')->setData($pageData)->save();
        return $this->_redirect('adminhtml/cms_page/edit', array('page_id' => $newCmsPage->getId()));
    }
}

