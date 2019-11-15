<?php
class Collinsharper_FraudBlock_Adminhtml_FaillogController extends Mage_Adminhtml_Controller_action
{

    /**
     * Init Adminhtml Action
     *
     * @return  void
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('sales/fraudblock')
            ->_addBreadcrumb(Mage::helper('fraudblock')->__('Fraud List'), Mage::helper('fraudblock')->__('Fail List'));

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
        $model  = Mage::getModel('fraudblock/chfraudtrack')->load($id);

        if (0 ) { //   $model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            if($model->getIp() && strpos('.',$model->getIp()) === false) {
                $model->setIp(long2ip($model->getIp()));
            }

            Mage::register('fraudblock_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('collinsharper/fraudblock');

            $this->_addBreadcrumb(Mage::helper('fraudblock')->__('Manage'), Mage::helper('fraudblock')->__('Manage'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('fraudblock/adminhtml_faillog_edit'))
                ->_addLeft($this->getLayout()->createBlock('fraudblock/adminhtml_faillog_edit_tabs'));

            $this->renderLayout();
        } else {
            //Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fraudblock')->__('Item does not exist'));
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fraudblock')->__('Please use the mass action to block the record.'));
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
                $model = Mage::getModel('fraudblock/chfraudtrack');


                $model->setData($data)
                    ->setId($this->getRequest()->getParam('id'));

                if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $model->setUpdateTime(now());
                }



                if($model->getIp() && (strstr($model->getIp(), '.'))) {
                    $model->setIp(ip2long($model->getIp()));
                }

                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('fraudblock')->__('Record was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fraudblock')->__('Unable to find information to save'));
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
        $model  = Mage::getModel('fraudblock/chfraudtrack')->load($id);

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
        $ids = $this->getRequest()->getParam('fraudblocks');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fraudblock')->__('Please select record(s)'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('fraudblock/chfraudtrack')->load($id);
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
    /**
     * Mass massBlock Action
     *
     * @return  void
     */
    public function massBlockAction()
    {
        $ids = $this->getRequest()->getParam('fraudblocks');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fraudblock')->__('Please select record(s)'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $record = Mage::getModel('fraudblock/chfraudtrack')->load($id);

                $model = Mage::getModel('fraudblock/fraudban');


                $model->setData($record->getData());
                $model->setData('cc_hash_a', $record->getData('cc_hash'));
                $model->setData('comment', 'Mass block from admin');
                $model->setData('banned', 1);

                if ($model->getCreatedTime() == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $model->setUpdateTime(now());
                }

                $model->save();


            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Total of %d record(s) were successfully blocked. Navigate to Sales > Fraud Tools > Fraud Ban to see banned records', count($ids)
                )
            );
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');

    }
}
