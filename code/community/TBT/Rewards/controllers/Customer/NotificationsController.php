<?php
/**
 * Customer Notifications Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Customer_NotificationsController extends Mage_Core_Controller_Front_Action 
{
    /*
     * Unsubscribe a customer from the Point Summary monthly notification - must have a valid customer id
     * to be unserialized prior to loading.
     */
    public function unsubscribeAction() 
    {
        $encryptedCustomerId = $this->getRequest()->getParam('customer');
        
        if ($encryptedCustomerId) {
            $customerId = (int) urldecode(base64_decode($encryptedCustomerId));
            $customer = Mage::getModel('rewards/customer')->load($customerId);
            if ($customer->getId()) {
                try {
                    $customer->setRewardsPointsNotification(0)->save();
                    $currencyCaption = Mage::helper('rewards/currency')->getDefaultFullCurrencyCaption();
                    $message = "You have successfully unsubscribed from all {$currencyCaption} notifications. <br/>"
                        . 'To resubscribe, review your <b>Email Preferences</b> on the %sAccount Dashboard%s page.';
                    
                    $myAccountUrl = Mage::getUrl('customer/account');
                    Mage::getSingleton('core/session')->addSuccess($this->__($message, "<a href='{$myAccountUrl}' title='My account'>", '</a>'));
                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->addException($e, $this->__('There was a problem unsubscribing you from this notification.'));
                }
            } else {
                
                $message = $this->__("The link you clicked on is outdated. Please login to continue with unsubscribing.");
                $message .= '<br />';
                $message .= $this->__("Alternatively, please follow the unsubscribe link on a more recent email.");
                        
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('newsletter/manage');
                return $this;
            }
        }
        
		$this->_redirect('/');

        return $this;
    }
    
    public function updateEmailPreferencesAction()
    {
        $customer = Mage::getModel('rewards/session')->getSessionCustomer();
        
        if (!$customer || !$customer->getId()) {
            $message = $this->__('Please login to update your email preferences.');
            Mage::getSingleton('customer/session')->addError($message);

            $this->_redirect('customer/account');
            return $this;
        }
        
        $emailPreferences = $this->getRequest()->getParam('email-preference');
        $emailPreferences = (!empty($emailPreferences));
        $customer->setRewardsPointsNotification($emailPreferences)->save();
        
        $message = $this->__('Your email preferences were updated.');
        Mage::getSingleton('customer/session')->addSuccess($message);
            
        $this->_redirect('customer/account');
        return $this;
    }
	
    /**
     *  Save Customer notification preference for points email
     */
    public function savePrefAction() {
        if ($this->getRequest()->isPost()) {
            $customerSession = Mage::getSingleton('rewards/session')->getSessionCustomer();
            try {
                $data = $this->getRequest()->getPost();
                $sendPointsNotification = isset($data['rewards_points_notification']) ? true : false;
                $customerSession->setRewardsPointsNotification($sendPointsNotification);
                
                $customerSession->save();

                Mage::getSingleton('core/session')->addSuccess($this->__("Your preferences were saved successfully."));
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addException($e, $this->__('There was a problem saving your preferences.'));
                Mage::logException($e);
            }
        }

        $this->_redirect('*/customer/');

        return $this;
    }
}
