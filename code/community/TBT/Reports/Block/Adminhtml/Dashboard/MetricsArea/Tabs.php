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
 * @package    [TBT_Reports]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Metrics Reports Tabs
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Main Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('dashboard_metricsArea_tabControls');
        $this->setTemplate('tbtreports/widget/tabs.phtml');
        $this->setDestElementId('dashboard_metricsArea_content');
    }
    
    /**
     * Add Tab
     * @param string $tabId
     * @param mixed $tab
     */
    public function addTab($tabId, $tab)
    {
        if (is_array($tab) && isset($tab['url'])) {
            $tab['url'] = $this->getUrl($tab['url']);
        }
        
        parent::addTab($tabId, $tab);
    }
    
    /**
     * Get Group Header
     * 
     * @param $tab
     * @return string
     */
    public function getTabPreHeader($tab)
    {
        return $this->_tabs[$this->getTabId($tab, false)]->getPre();
    }
    
    /**
     * Set active tab
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $reportCode = Mage::app()->getRequest()->getParam('report_code');
        if ($reportCode) {
            $this->setActiveTab('dashboard_metrics_tab_' . $reportCode);
        }
        
        return parent::_beforeToHtml();
    }
}

