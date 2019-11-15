<?php
class Collinsharper_Ordericons_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    /**
     * Init Adminhtml Action
     *
     * @return  void
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('sales/chordericons')
            ->_addBreadcrumb(Mage::helper('chordericons')->__('Order Icons'), Mage::helper('chordericons')->__('Order Icons'));

        return $this;
    }


    /**
     * Default Index Action
     *
     * @return  void
     */
    public function ajaxLoadOrderIconDivAction() {

        $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : false;
        $activeIcons = array();
        if($order_id) {
            $x = Mage::getModel('chordericons/chordericons')->getCollection();
            $x->addFieldToFilter('order_id', $order_id);
            foreach( $x as $icon) {
                $activeIcons[] = $icon->getData('icon_id');
            }
        }
        echo $this->getLayout()->createBlock("core/template")->setName("sales_order.grid.chordericons")->setTemplate("chordericons/ordergridadd.phtml")->setIsAjax(true)->setActiveIcons($activeIcons)->setOrderId($order_id)->toHtml();
        return;
    }

    /**
     * Default Index Action
     *
     * @return  void
     */
    public function ajaxSaveOrderIconDivAction() {

        $orderId = isset($_POST['order_id']) ? $_POST['order_id'] : false;
        $orderIconsData = isset($_POST['iconIds']) ? $_POST['iconIds'] : false;
        $comment = isset($_POST['comment']) ? $_POST['comment'] : false;

        if($orderId && $orderIconsData) {
            $orderIconsData = trim($orderIconsData,",");
            $orderIcons = explode(',', $orderIconsData);

          //  $order = Mage::getModel('sales/order')->load($orderId);
            // we just fake an order..
            $order = new Mage_Sales_Model_Order;
            $order->setId($orderId);
//  TODO this does nothing
            //$order->setChOrderIcons($orderIconsData);

            $x = Mage::getModel('chordericons/chordericons')->getCollection();
            $x->addFieldToFilter('order_id', $orderId);
            foreach( $x as $icon) {
                if(!in_array($icon->getIconId(), $orderIcons)) {
                    $icon->delete();
                } else {
                    array_splice($orderIcons, array_search($icon->getIconId(), $orderIcons), 1 );
                }
            }
	    try {
	        Mage::helper('chordericons')->setOrderIcons($orderId, $orderIcons, $comment);
	    } catch (Exception $e) {
		Mage::logException($e);
	    }

            $x = new Collinsharper_Ordericons_Block_Adminhtml_Renderer_Ordericons;
            echo $x->render($order);
        }

        return;

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
        $model  = Mage::getModel('chordericons/chicons')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('chordericons_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('collinsharper/chordericons');

            $this->_addBreadcrumb(Mage::helper('chordericons')->__('Manage'), Mage::helper('chordericons')->__('Manage'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('chordericons/adminhtml_chicons_edit'))
                ->_addLeft($this->getLayout()->createBlock('chordericons/adminhtml_chicons_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chordericons')->__('Item does not exist'));
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
                $model = Mage::getModel('chordericons/chicons');
                $x = new Collinsharper_Ordericons_Helper_Data;
                $mediaPath = ($x->getMediaPath() . DS) ;

                if (isset($data['image']['delete']) && $data['image']['delete'] == 1) {
                    @unlink($mediaPath . DS . $data['image']['value']);
                    $data['image'] = '';
                } else {
                    if (!empty($_FILES['image']) && $_FILES['image']['name']) {

                        $uploader = new Mage_Core_Model_File_Uploader('image');
                        $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                        if (file_exists($mediaPath . DS .  $_FILES['image']['name'])) {
                            @unlink($mediaPath . $_FILES['image']['name']);
                        }
                        $out = $uploader->save($mediaPath, $_FILES['image']['name']);

                        $data['image'] = $out['file'];
                    } else {
                        // Skip image
                        unset($data['image']);
                    }
                }

                $model->setData($data)
                    ->setId($this->getRequest()->getParam('id'));

                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('chordericons')->__('Record was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chordericons')->__('Unable to find information to save'));
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
        $model  = Mage::getModel('chordericons/chicons')->load($id);

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
        $ids = $this->getRequest()->getParam('itemIds');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chordericons')->__('Please select record(s)'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('chordericons/chicons')->load($id);
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
