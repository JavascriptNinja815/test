<?php
/**
 * Collinsharper/Chcustom/Helper/Data.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Custom Extension Helper Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */

class Collinsharper_Chcustom_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Generic Debug Log function
     *
     * @param string|null $_message Debug Message
     * @param int|null    $_level   Magento Log level
     *
     * @return  void
     */
    public function debugLog($_message, $_level = null)
    {
        Mage::log($_message, $_level, "ch_chcustom.log");
    }

    /**
     * Retrieve Customer Object
     *
     * @return  Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     * Get Customer ID
     *
     * @return  int
     */
    public function getCustomerId()
    {
        // Try to return the active customer
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getCustomer()->getId();
        } else if (Mage::registry('current_customer')) {
            return Mage::registry('current_customer')->getId();
        }

    }

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    public function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Setup Speed Chart Column in the Grid
     *
     * @return array
     */
    public function setSpeedChartsColumn()
    {
        return array(
            'header'        => 'Speed Charts',
            'index'         => array('speed_chart_a', 'speed_chart_b'),
            'type'          => 'concat',
            'separator'     => ', ',
            'filter_index'  => "CONCAT(`speed_chart_a`, ', ', `speed_chart_b`)",
            'filter_condition_callback' => array('Collinsharper_Chcustom_Model_Observer', 'filterSpeedCharts'),
        );
    }

    /**
     * Mark Approval function
     *
     * @param object|null $order Sales Order Model
     * @param int|null    $approval Magento Approval Status -1 = Not Approved, 1 = Approved
     * @param object|null $approver Mage_Customer_Model
     *
     * @throws Exception
     * @return  string
     */
    public function markApproval($order, $approval, $approver = null)
    {
        $result = "";

        try {
            // Mark the order approved
            if ($approver == null) {
                $customerId = $this->_getSession()->getCustomerId();
                $approver = Mage::getModel('customer/customer')->load($customerId);
            }

            // Get the additional data record
            $additionalOrderDetails = Mage::getModel('chcustom/additionalorderdetails')->getAdditionalInformationBySalesOrderId($order->getId());

            if ($additionalOrderDetails == null) {
                throw new Exception($this->__('Unable to find the corresponding additional order details'));
            } else {
                $additionalOrderDetailsId = $additionalOrderDetails->getId();
            }

            // Make sure we have the right approver
            if ($additionalOrderDetails->getData('approver_a') != $approver->getEmail() &&
                $additionalOrderDetails->getData('approver_b') != $approver->getEmail())
            {
                throw new Exception($this->__('You are not authorize to approve this order'));
            }

            // Now do the approval
            if ($additionalOrderDetails->getData('approver_a') == $approver->getEmail()) {
                $additionalOrderDetails->setData('approval_a', $approval);
            }

            if ($additionalOrderDetails->getData('approver_b') == $approver->getEmail()) {
                $additionalOrderDetails->setData('approval_b', $approval);
            }

            // Okay, now we need to save the data
            $additionalOrderDetails->setId($additionalOrderDetailsId);
            $additionalOrderDetails->save();

            // If we have both approver approval. mark the order processing
            if ($additionalOrderDetails->getData('speed_chart_b') == null) {
                // We only need approver A
                if ($additionalOrderDetails->getData('approval_a') == 1) {
                    $result = $this->approveOrder($order);
                } else {
                    $result = $this->disapproveOrder($order);
                }
            } else {
                // We need both A and B approved
                if ($additionalOrderDetails->getData('approval_a') == 1 &&
                    $additionalOrderDetails->getData('approval_b') == 1) {
                    $result = $this->approveOrder($order);
                } else {
                    // Do we have a disapproval?
                    if ($approval == -1) {
                        $result = $this->disapproveOrder($order);
                    } else {
                        $result = $this->approvepartialOrder($order);
                    }
                }
            }

        } catch (Exception $e) {
            $this->debugLog(__FILE__. ' ' .__LINE__ . ' ' . $e->getMessage());
            throw new Exception($e->getMessage());
        }

        return $result;
    }

    /**
     * Convert Order to Processing when all approver approved
     *
     * @param object|null $order Sales Order Model
     *
     * @return  string
     */
    private function approveOrder($order)
    {
        // Update Order Status
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
        return $this->__('You had approved the order %s, the order will now ready for processing', $order->getData('increment_id'));
    }

    /**
     * Convert Order to Processing when all approver partial approved
     *
     * @param object|null $order Sales Order Model
     *
     * @return  string
     */
    private function approvepartialOrder($order)
    {
        return $this->__('You had approved the order %s. Waiting for another approver for approval', $order->getData('increment_id'));
    }

    /**
     * Cancel Order when either approver disapprove the order
     *
     * @param object|null $order Sales Order Model
     *
     * @return  string
     */
    private function disapproveOrder($order)
    {
        // Update Order Status
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
        return $this->__('You had disapproved the order %s, the order will now cancel', $order->getData('increment_id'));
    }

    /**
     * Send Email to the Approver for approval request
     *
     * @param object|null $sales_order_increment_id Sales Order Model
     *
     * @return  void
     */
    public function sendApproverNotice($_order)
    {
        // Retrieve the Additional Details
        $additional_details = Mage::getModel('chcustom/additionalorderdetails')->getAdditionalInformationBySalesOrderId($_order->getId());

        if ($additional_details != null) {
            // send email to approver a
            $storeId = Mage::app()->getStore()->getId();
            $_order->setData("ApprovalLink", Mage::getUrl('collinsharper_chcustom/approval/view', array('order_id' => $_order->getId(), '_secure' => Mage::app()->getStore()->isFrontUrlSecure())));

            if (strlen(trim($additional_details->getData('approver_a'))) > 0) {
                // Get the approver information
                $approver_username = str_ireplace('@ead.ubc.ca', '', $additional_details->getData('approver_a'));
                $approver = Mage::getModel('chcustom/approver_cache')->getApproverByUsername($approver_username);

                if ($approver != null) {
                    if (strlen(trim($approver->getEmail())) > 0) {
                        $translate = Mage::getSingleton('core/translate');

                        /* @var $translate Mage_Core_Model_Translate */
                        $translate->setTranslateInline(false);
                        Mage::getModel('core/email_template')
                            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
                            ->sendTransactional(
                            Mage::getStoreConfig('collinsharper/approver_email_settings/email_template', $storeId),
                            Mage::getStoreConfig('collinsharper/approver_email_settings/email_identity', $storeId),
                            $approver->getEmail(),
                            $approver->getFullname(),
                            array('$approver_name' => $approver->getFullname(), 'sales' => $_order)
                        );
                        $translate->setTranslateInline(true);
                    }
                }
            }

            // send email to approver b
            if (strlen(trim($additional_details->getData('approver_b'))) > 0) {
                // Get the approver information
                $approver_username = str_ireplace('@ead.ubc.ca', '', $additional_details->getData('approver_b'));
                $approver = Mage::getModel('chcustom/approver_cache')->getApproverByUsername($approver_username);

                if ($approver != null) {
                    if (strlen(trim($approver->getEmail())) > 0) {
                        $translate = Mage::getSingleton('core/translate');

                        /* @var $translate Mage_Core_Model_Translate */
                        $translate->setTranslateInline(false);
                        Mage::getModel('core/email_template')
                            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
                            ->sendTransactional(
                            Mage::getStoreConfig('collinsharper/approver_email_settings/email_template', $storeId),
                            Mage::getStoreConfig('collinsharper/approver_email_settings/email_identity', $storeId),
                            $approver->getEmail(),
                            $approver->getFullname(),
                            array('$approver_name' => $approver->getFullname(), 'sales' => $_order)
                        );
                        $translate->setTranslateInline(true);

                    }
                }
            }
        }
    }
}
