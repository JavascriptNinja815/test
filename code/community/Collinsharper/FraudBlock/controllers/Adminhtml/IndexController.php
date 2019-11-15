<?php
class Collinsharper_FraudBlock_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    /**
     * Init Adminhtml Action
     *
     * @return  void
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('sales/fraudblock')
            ->_addBreadcrumb(Mage::helper('fraudblock')->__('Fraud Ban'), Mage::helper('fraudblock')->__('Fraud Ban'));

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
        $model  = Mage::getModel('fraudblock/fraudban')->load($id);

        if ($model->getId() || $id == 0) {
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

            $this->_addBreadcrumb(Mage::helper('fraudblock')->__('Manage Ban'), Mage::helper('fraudblock')->__('Manage Ban'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('fraudblock/adminhtml_fraudblock_edit'))
                ->_addLeft($this->getLayout()->createBlock('fraudblock/adminhtml_fraudblock_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fraudblock')->__('Item does not exist'));
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
                $model = Mage::getModel('fraudblock/fraudban');


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
        $model  = Mage::getModel('fraudblock/fraudban')->load($id);

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
                $model = Mage::getModel('fraudblock/fraudban')->load($id);
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
