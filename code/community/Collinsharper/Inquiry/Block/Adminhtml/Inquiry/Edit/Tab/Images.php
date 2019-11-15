<?php
class Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Edit_Tab_Images extends Mage_Adminhtml_Block_Widget_Form
//class Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Edit_Tab_Images extends Collinsharper_Chcustomeruploads_Block_Adminhtml_Sales_Order_View_Tab_Action
{
    var $_order_id = null;

    public function __construct()
    {
        parent::__construct();
    //    $this->setDestElementId('image_form');
        $this->setTemplate('chuploads/action.phtml');
    }


    protected function _prepareLayout()
    {
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled() && ($block = $this->getLayout()->getBlock('head'))) {
            $block->setCanLoadTinyMce(true);
        }
        return parent::_prepareLayout();
    }

    function getInquiry() {
        if($this->_inquiry) {
            return $this->_inquiry;
        }
        $model = Mage::registry('chinquiry_data');
        if($this->getInquiry()) {
            $model = $this->getInquiry();
        }
        return $model;
    }

    function setInquiry($x) {
        $this->_inquiry = $x;
    }

    protected function __prepareForm()
    {
        /* @var $model Collinsharper_Inquiry_Model_Inquiry */
        $model = $this->getInquiry();

        $form = new Varien_Data_Form(array(
                'id' => 'image_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array(
                'add_variables'             => false,
                'add_widgets'               => false,
                'files_browser_window_url'  => $this->getBaseUrl().'admin/cms_wysiwyg_images/index/')
        );

        $fieldset = $form->addFieldset('chinquiry_image', array('legend'=>Mage::helper('chinquiry')->__('Images')));


//        $x = new Collinsharper_Chcustomeruploads_Block_Adminhtml_Sales_Order_View_Tab_Action;

        $content = $this->getLayout()->createBlock('collinsharper_chcustomeruploads/adminhtml_sales_order_view_tab_action')
            ->setOrderId($model->getData('increment_id'))->toHtml();



        $fieldset->addField('note', 'note', array(
            'text'     => $content,
        ));

        if ( Mage::getSingleton('adminhtml/session')->getFraudblockData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getFraudblockData());
            Mage::getSingleton('adminhtml/session')->setFraudblockData(null);
        } elseif ( Mage::registry('chinquiry_data') ) {
            $form->setValues(Mage::registry('chinquiry_data')->getData());
        }

        return parent::_prepareForm();
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/chinquiry');
    }


    public function getFileUploads($inquiry = false)
    {
        if($inquiry) {
            $this->setInquiry($inquiry);
        }
        return $this->getOrderUploads();
    }

    public function setOrderId($x)
    {
        $this->_order_id = $x;
        return $this;
    }

    function isStereoInquiry($model = false)
    {
        if(!$model) {
            $model = Mage::registry('chinquiry_data');
        }

        return $model && $model->getData('inquiry_type') == Collinsharper_Inquiry_Model_Source_Inquirytype::STEREO_INQUIRY;
    }

    public function getOrderId()
    {
        if( $this->_order_id  ) { return  $this->_order_id ; }
        $model = Mage::registry('chinquiry_data');

        if(!$model && $this->getInquiry()) {
            $model = $this->getInquiry();
        }

        if($this->isStereoInquiry($model)) {
            return $model->getQuoteId();
        }

        if($model && $model->getIncrementId() ) {
            return Mage::registry('chinquiry_data')->getIncrementId();
        }
        if($model && $model->getId() ) {
            return $model->getId();
        }

        return  (Mage::registry('current_order') ? Mage::registry('current_order')->getIncrementId() : false);
    }

    public function getCollection()
    {
        $collection = Mage::getModel('collinsharper_chcustomeruploads/upload')->getCollection();
        //  $collection = new Collinsharper_Chcustomeruploads_Model_Resource_Upload_Collection;
        $collection
            ->addFieldToSelect('*')
            //   ->addFieldToFilter('customer_id', $this->getCustomerId())
            // ->addFieldToFilter('status', '1')
        ;
        return $collection;
    }

    public function getOrderUploads()
    {

        $data = array();
        $orderId = $this->getOrderId();

        mage::log(__METHOD__ . " and order " . $orderId);
        if(!$orderId) {
            return $data;
        }

        $collection = $this->getCollection();
        //TODO: these are backwords in query
        //$collection->addFilter('order_ids', $this->getOrderId());
        //$collection->getSelect()->where("{$this->getOrderId()} in (main_table.order_ids)");
        //  $collection->addFilter('order_id', $this->getOrder()->getIncrementId());

//        $orderId = $this->getOrderIncrementId();

        mage::log(__METHOD__ . __LINE__ . " we have " . $orderId);

        if($this->isStereoInquiry()) {
            $collection->getSelect()->where("'{$orderId}' in (main_table.quote_ids)");
        } else {
            $collection->getSelect()->where("'{$orderId}' in (main_table.quote_ids)");
        }

        mage::log(__METHOD__ . __LINE__ . "{ sel " .  $collection->getSelect()->__toString());
        foreach($collection as $c) {
            $data[] = $c;
        }
        return $data;
    }


}
