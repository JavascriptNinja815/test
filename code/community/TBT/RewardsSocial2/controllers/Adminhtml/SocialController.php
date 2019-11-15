<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
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
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * RewardsSocial Social Controller
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Adminhtml_SocialController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Acl check for admin
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    public function migrationAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function doMigrateAction()
    {
        $action = $this->getRequest()->getParam('action');
        $page = $this->getRequest()->getParam('page');
        
        $result = Mage::helper('rewardssocial2/migration')->migrateData($action, $page);
        
        if (!$result) {
            $this->getResponse()->setBody($this->__('There were some problems while migrating your data. Some entries might not have been transfered succesfully.'));
        }
        
        return $this;
    }
    
    public function cleanUpAction()
    {
        $helper = Mage::helper('rewardssocial2/migration');
        $error = false;
        $response = array(
            'success'       => false,
            'message'       => null,
            'error_code'    => null
        );
        
        try {
            $helper->dropOldData();
            if (!$helper->disableSocialModules()) {
                $error = true;
                $response['message'] = $this->__("Unable to disable MageRewards Social 1.0.");
                $response['error_code'] = 'file_permissions';
            }
        } catch (Exception $e) {
            $error = true;
            $response['message'] = $e->getMessage();
            Mage::log($e->__toString(), Zend_Log::ERR, TBT_RewardsSocial2_Helper_Migration::LOG_FILE);
        }
        
        if (!$error) {
            $response['success'] = true;
            Mage::app()->cleanCache();
            $message = "Migration Successful. Please double check the new "
                . "configurations and try out the customer experience "
                . "on the store's front-end.";
            
            Mage::getSingleton('core/session')->addSuccess($message);
        }


        $this->getResponse()->clearAllHeaders()
            ->setHeader('Content-Type', 'application/javascript')
            ->setBody(json_encode($response));
        
        return $this;
    }
}
