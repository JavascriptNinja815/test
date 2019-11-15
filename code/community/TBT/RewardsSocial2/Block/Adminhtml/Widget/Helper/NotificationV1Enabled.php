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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * Widget Notification V1 Enabled Helper for Social Block
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Block_Adminhtml_Widget_Helper_NotificationV1Enabled
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Prepare element html
     * @param Varien_Data_Form_Element_Abstract $element
     * @return \Varien_Data_Form_Element_Abstract
     */
    public function prepareElementHtml(Varien_Data_Form_Element_Abstract $element) {
        if (!Mage::helper('rewardssocial2')->isRewardsSocialV1Enabled()) {
            return $element;
        }

        $notificationLink = Mage::helper("adminhtml")
            ->getUrl('adminhtml/adminhtml_social/migration');
        $notificationLinkText = $this->__('Fix Now');
        $notificationText = $this->__("This widget will not function because you're using an older version of the MageRewards Social component.");
        
        $notifications = array(
            array(
                'text' => $notificationText,
                'link' => $notificationLink,
                'linkText' => $notificationLinkText
            )
        );
        
        $notificationBlock = $this->getLayout()->createBlock('adminhtml/template')
            ->setTemplate('rewards/dashboard/widget/notifications.phtml')
            ->setNotifications($notifications);
        
        $element->setStyle('display:none');
        $element->setLabel('');
        $element->setData(
            'after_element_html', $notificationBlock->toHtml()
        );
        
        return $element;
    }
}