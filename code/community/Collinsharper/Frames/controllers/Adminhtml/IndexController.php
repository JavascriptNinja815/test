<?php
/**
 * Collinsharper/Chshopby/controllers/Adminhtml/IndexController.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Chshopby_Adminhtml_IndexController
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Frames_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    /**
     * Init Adminhtml Action
     *
     * @return  void
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('customers/frames')
            ->_addBreadcrumb(Mage::helper('chframes')->__('Manage Frames'), Mage::helper('chframes')->__('Manage Frames'));

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
        $model  = Mage::getModel('chframes/frames')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('frames_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('customers/chframes');

            $this->_addBreadcrumb(Mage::helper('chframes')->__('Manage Frames'), Mage::helper('chframes')->__('Manage Frames'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('chframes/adminhtml_frames_edit'))
                ->_addLeft($this->getLayout()->createBlock('chframes/adminhtml_frames_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chframes')->__('Item does not exist'));
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
        // We only do the POST update
        if ($data = $this->getRequest()->getPost())
        {
            try {
                $model = Mage::getModel('chframes/frames');

                $model->setData($data)
                    ->setId($this->getRequest()->getParam('id'));

                if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $model->setUpdateTime(now());
                }

                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('chframes')->__('Reel information was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chframes')->__('Unable to find frame information to save'));
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
        $model  = Mage::getModel('chframes/frames')->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Record does not exist'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $title = $model->getData('frame_name');
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__('%s has been successfully deleted', $title));
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
        $ids = $this->getRequest()->getParam('frameIds');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chframes')->__('Please select record(s)'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('chframes/frames')->load($id);
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
