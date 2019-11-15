<?php
class Collinsharper_Inquiry_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    protected $_status_update = false;
    /**
     * Init Adminhtml Action
     *
     * @return  void
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('sales/chinquiry')
            ->_addBreadcrumb(Mage::helper('chinquiry')->__('Inquiry'), Mage::helper('chinquiry')->__('Inquiry'));

        return $this;
    }

    /**
     * Default Index Action
     *
     * @return  void
     */
    public function indexAction() {
        $this->_initAction()->renderLayout();
    }

//markarchive
    /*
     *
    class Collinsharper_Inquiry_Model_Source_Status
    {

    const NEW = 1;
    const VIEWED = 2;
    const ARCHIVED = 3
    const DELETED = 9;


     */

    public function sendemailAction()
    {
        $id     = (int)$this->getRequest()->getParam('id');
        $inquiry  = Mage::getModel('chinquiry/inquiry')->load($id);
        $inquiryHelper = Mage::getModel('chinquiry/source_inquirytype');
        $org_inquiry_info = $inquiry->getData( 'post_data' );
        $order_data = unserialize( $org_inquiry_info );
        $orderData = new Varien_Object($order_data);

        $emailData = $inquiryHelper->buildInquiryEmailData($orderData, $inquiry);
    //    mage::log(__METHOD__  ." and emaildata " . serialize($emailData->getData())  );

        $emailResult = $inquiryHelper->sendInquiryEmailData($emailData);

        if($emailResult) {
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('chinquiry')->__('Email Sent.'));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chinquiry')->__('Email not Sent.'));
        }

        $this->_redirect('*/*/edit', array('id' => $id));
    }

    public function markarchiveAction()
    {
        $this->_status_update = Collinsharper_Inquiry_Model_Source_Status::STATE_ARCHIVED;
        return $this->updatestatusAction();
    }

    public function markreadAction()
    {
        $this->_status_update = Collinsharper_Inquiry_Model_Source_Status::STATE_VIEWED;
        $this->updatestatusAction();
    }

    public function updatestatusAction()
    {
        $id     = $this->getRequest()->getParam('id');
        $inbound_status = (int)$this->getRequest()->getParam('status_id');
        $model  = Mage::getModel('chinquiry/inquiry')->load($id);
        if(!$this->_status_update && $inbound_status) {
            $this->_status_update = $inbound_status;
        }
        if($model && $model->getId() && $this->_status_update) {
            try {
                $model->setStatus($this->_status_update);
                $model->save();
                $this->_status_update = false;
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('chinquiry')->__('Item updated'));

            } catch (Exception $e ) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chinquiry')->__('Item NOT updated %s ', $e->getMessage()));

            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chinquiry')->__('Item does not exist'));
        }
        $this->_redirect('*/*/index');
    }

    /**
     * New Record Action
     *
     * @return  void
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit Shopby Record Action
     *
     * @return  void
     */
    public function editAction()
    {
        $id     = $this->getRequest()->getParam('id');
        $model  = Mage::getModel('chinquiry/inquiry')->load($id);

        if($model->getStatus() == 1) {
            $model->setStatus(2);
            $model->save();
        }

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            if($model->getIp() && strpos('.',$model->getIp()) === false) {
                $model->setIp(long2ip($model->getIp()));
            }

            Mage::register('chinquiry_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('collinsharper/chinquiry');

            $this->_addBreadcrumb(Mage::helper('chinquiry')->__('Manage Ban'), Mage::helper('chinquiry')->__('Manage Ban'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('chinquiry/adminhtml_inquiry_edit'))
                ->_addLeft($this->getLayout()->createBlock('chinquiry/adminhtml_inquiry_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chinquiry')->__('Item does not exist'));
            $this->_redirect('*/*/');
        }
    }

    /**
     * Save Shopby Record Action
     *
     * @return  void
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost())
        {

            try {
                $model = Mage::getModel('chinquiry/inquiry');


                $model->load($this->getRequest()->getParam('id'));

                if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $model->setUpdateTime(now());
                }



                if($model->getIp() && (strstr($model->getIp(), '.'))) {
                    $model->setIp(ip2long($model->getIp()));
                }

                if(isset($data['new_comment'])) {
                    $newComment = (string)( (string) $model->getData('comment_field')) .
                        "\n\n" .
                        ( (string) Mage::getModel('core/date')->date('Y-m-d H:i:s')) . ' - ' . ((string)Mage::getSingleton('admin/session')->getUser()->getUsername()) . ': ' . $data['new_comment'];
                    $model->setData('comment_field', $newComment );
                }

                $model->setData('status', (int)$data['status']+5);

                $model->save();
                $model->load($this->getRequest()->getParam('id'));


                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('chinquiry')->__('Record was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chinquiry')->__('Save is currently unsupported'));
        $this->_redirect('*/*/');
    }

    /**
     * Single Deletion Action
     *
     * @return  void
     */
    public function deleteAction()
    {
        $id     = (int) $this->getRequest()->getParam('id');
        $model  = Mage::getModel('chinquiry/inquiry')->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Record does not exist'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__('Record has been successfully deleted'));
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    /**
     * Mass Deletion Action
     *
     * @return  void
     */
    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('chinquirys');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chinquiry')->__('Please select record(s)'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('chinquiry/inquiry')->load($id);
                $model->delete();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Total of %d record(s) were successfully deleted', count($ids)
                )
            );
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');

    }

}
