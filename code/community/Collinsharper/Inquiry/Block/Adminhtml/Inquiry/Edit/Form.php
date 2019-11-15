<?php
class Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareLayout()
    {
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled() && ($block = $this->getLayout()->getBlock('head'))) {
            $block->setCanLoadTinyMce(true);
        }
        return parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        /* @var $model Collinsharper_Inquiry_Model_Inquiry */
        $model = Mage::registry('chinquiry_data');

        /*
         * Checking if user have permissions to save information, for future use only
         *
        if ($this->_isAllowedAction('save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }
        */
        $isElementDisabled = false;

        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
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

        $fieldset = $form->addFieldset('chinquiry_form', array('legend'=>Mage::helper('chinquiry')->__('information')));
        /*
                $fieldset->addField('customer_id', 'text', array(
                    'label'     => Mage::helper('chinquiry')->__('Customer ID'),
                    //'class'     => 'required-entry',
                    // 'required'  => true,
                    'name'      => 'customer_id',
                ));

                $fieldset->addField('ip', 'text', array(
                    'label'     => Mage::helper('chinquiry')->__('IP'),
                    //'class'     => 'required-entry',
                    // 'required'  => true,
                    'name'      => 'ip',
                ));

                $fieldset->addField('email', 'text', array(
                    'label'     => Mage::helper('chinquiry')->__('Email'),
                    //'class'     => 'required-entry',
                    // 'required'  => true,
                    'name'      => 'email',
                ));

                 */
        /*
         * we load the dynamic block to show each differtnyt inquiry type
         *
         */
        $type = (int)$model->getData('inquiry_type');
        //    MAge::register('current_inquiry', $model);
        $blockIdentifier = Mage::getSingleton('chinquiry/source_inquirytype')->getBlockId($type);
        $content = "Sorry, unknown block type";
        // TODO abstract this up to object class
        if($model->getData('ip')) {
            $model->setData('ip', long2ip($model->getData('ip')));
        }

        $noQuoteIdSpecified = '
<div class="clear"></div>
<div class="box-left">
	<div class="entry-edit">
		<div class="entry-edit-head">
			<h4 class="icon-head head-account">Invalid Quote ID</h4>
		</div>
		<div class="fieldset">
		There was no Quote ID on the order inquiry or the Quote ID was invalid.
		</div>
    </div>
</div>
		';
        if($blockIdentifier) {
            try {
                $content = $this->_getChTemplate($model);

                // if we have a quote id add it to the data.
                if((int)$model->getData('inquiry_type') == Collinsharper_Inquiry_Model_Source_Inquirytype::ORDER_INQUIRY && $model->getData('quote_id')) {
                    $quoteData = Mage::getModel('chinquiry/inquiry')->load($model->getData('quote_id'));
                    // TODO abstract this up to object class
                    if($quoteData->getData('ip')) {
                        $quoteData->setData('ip', long2ip($quoteData->getData('ip')));
                    }

                    if($quoteData && $quoteData->getData('entity_id') == $model->getData('quote_id')) {

                        $content .= $this->_getChTemplate($quoteData);

                    } else {
                        $content .= $noQuoteIdSpecified;
                    }
                } else {
                    $content .= $noQuoteIdSpecified;
                }

            } catch (Exception $e) {
                //  echo " Exception " . $e->getMessage();
                mage::logException($e);
            }

        } else {
            try {


                $x = $model->getData();

                unset($x['post_data']);
                $content = " Generic Data: <br />";
                $content .= print_r($x, 1);
                $content .= " <br />";

                if($model->getData('post_data')) {
                    $x = unserialize($model->getData('post_data'));
                    $content .= "Posted Data: ". print_r($x, 1);
                    $content .= " <br />";

                }
            } catch (Exception $e) {
                //   echo __LINE__ . " Exception " . $e->getMessage();
                mage::logException($e);
            }

        }

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('chinquiry')->__('Status'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'status',
           // 'value'  => '1',
            'values' =>  MAge::getModel('chinquiry/source_status')->getOptions(),

        ));


        if($model->getData('comment_field')) {
            $fieldset->addField('comment_field', 'textarea', array(
                'label'     => Mage::helper('chinquiry')->__('Comments'),
                //'class'     => 'required-entry',
                // 'required'  => true,
                'name'      => 'comment_field',
                'readonly'      => 'true',
                // 'value'  => '1',

            ));
        }


        $fieldset->addField('new_comment', 'textarea', array(
            'label'     => Mage::helper('chinquiry')->__('Add Comment'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'new_comment',
           // 'value'  => '1',

        ));


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

    function _getChTemplate($model)
    {
        $blockIdentifier = Mage::getSingleton('chinquiry/source_inquirytype')->getBlockId((int)$model->getData('inquiry_type'));
        return  $this->getLayout()->createBlock('core/template')
            ->setStoreId($model->getData('store_id') || 0)
            ->setTemplate($blockIdentifier)
            ->setName('admin_i3d_' . '_' . $blockIdentifier . '_' . md5(serialize($model->getData())))
            ->setInquiry($model)->toHtml();
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
}
