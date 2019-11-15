<?php
/**
 * Collinsharper/Chshopby/controllers/Adminhtml/IndexController.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
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
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Reels_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    /**
     * Init Adminhtml Action
     *
     * @return  void
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('customers/Reels')
            ->_addBreadcrumb(Mage::helper('chreels')->__('Manage Reels'), Mage::helper('chreels')->__('Manage Reels'));

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
        $model  = Mage::getModel('chreels/reels')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('reels_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('customers/chreels');

            $this->_addBreadcrumb(Mage::helper('chreels')->__('Manage Reels'), Mage::helper('chreels')->__('Manage Reels'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('chreels/adminhtml_reels_edit'))
                ->_addLeft($this->getLayout()->createBlock('chreels/adminhtml_reels_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chreels')->__('Item does not exist'));
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
                $model = Mage::getModel('chreels/reels');

                $model->setData($data)
                    ->setId($this->getRequest()->getParam('id'));

                if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    //$model->setUpdateTime(now());

                    // we dont update the reel form the admin the user does that

                    $model->setUpdateTime(now());
                    $model->setData('forced_updated_at', true);

                }

                $model->save();

		//Add to print queue if quantity is set
		$printQty = (int)$this->getRequest()->getParam('print_qty', 0);

		if ($printQty) {
			$qmodel = Mage::getModel('chreels/printer');
			//Get the admin username
			$user = Mage::getSingleton('admin/session');
			$userName = $user->getUser()->getUsername();
			$qmodel->queueReel($model, $printQty, $userName);
		}

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('chreels')->__('Reel information was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chreels')->__('Unable to find reel information to save'));
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
        $model  = Mage::getModel('chreels/reels')->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Record does not exist'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $title = $model->getData('reel_name');
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
        $ids = $this->getRequest()->getParam('reelIds');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chreels')->__('Please select record(s)'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('chreels/reels')->load($id);
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
	 * Grid in Tab Action
	 *
	 * return void
	 */
	public function framesAction()
	{
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('chreels/adminhtml_reels_edit_tab_frames')
		);
	}

}
