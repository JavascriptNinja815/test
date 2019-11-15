<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Giftcard
 * @version    1.0.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

class AW_Giftcard_Adminhtml_GiftcardController extends Mage_Adminhtml_Controller_Action
{
    const GIFTCARD_VALUE = '14.95';
    protected function _initGiftCard()
    {
        $giftcardModel = Mage::getModel('aw_giftcard/giftcard');
        $giftcardId  = (int) $this->getRequest()->getParam('id', false);
        if ($giftcardId) {
            try {
                $giftcardModel->load($giftcardId);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        if (null !== Mage::getSingleton('adminhtml/session')->getFormData()
            && is_array(Mage::getSingleton('adminhtml/session')->getFormData())
        ) {
            $giftcardModel->addData(Mage::getSingleton('adminhtml/session')->getFormData());
            Mage::getSingleton('adminhtml/session')->setFormData(null);
        }
        Mage::register('current_giftcard', $giftcardModel);
        return $giftcardModel;
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('customer/giftcard');

        $this
            ->_title($this->__('Customers'))
            ->_title($this->__('Gift Cards'))
        ;
        return $this;
    }

    protected function _csvDownload($strGcIds)
    {
	$newIds = implode(',', $strGcIds);
	$currDateTime = date('Y-m-d_H-i-s');
        $filename = 'RV-Giftcard'.$currDateTime.'.csv';
        $helper =  Mage::helper('aw_giftcard/rvcsv');
        $helper->setList($newIds);
        $content = $helper->generateGcList();
        $this->_prepareDownloadResponse($filename, $content);
    }

    public function indexAction()
    {
        $this->_initAction();
        $this->_title($this->__('Manage Gift Cards'));
        $this->renderLayout();

/*
	$gcIds = Mage::getSingleton('adminhtml/session')->getNewGcIds();
	if(!empty($gcIds))
	{
	    //$this->_csvDownload($gcIds);
	    Mage::getSingleton('adminhtml/session')->unsNewGcIds();
	}
*/
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function newrvAction()
    {
    	$giftCardParams = $this->getRequest()->getParams();
    	$errors = array();
    	if (isset($giftCardParams['prefix_code']) && $giftCardParams['prefix_code']) {
    		$rvGifcardPrefix = $giftCardParams['prefix_code'];
    	} else {
    		$errors[] = $this->__('Prefix Code field is not empty.');
    	}
    	if (isset($giftCardParams['how_many']) && $giftCardParams['how_many']) {
    		$rvGifcardCount = $giftCardParams['how_many'];
    		if (!is_numeric($rvGifcardCount)) {
    			$errors[] = $this->__('How Many field is required and it must be a number greater than zero.');
    		} else if (is_numeric($rvGifcardCount) && $rvGifcardCount < 1) {
    			$errors[] = $this->__('How Many field is required and it must be a number greater than zero.');
    		}
    	} else {
    		$errors[] = $this->__('How Many field is required and it must be a number greater than zero.');
    	}
    	if (isset($giftCardParams['redemption_code_cost']) && $giftCardParams['redemption_code_cost']) {
    		$redemption_code_cost = $giftCardParams['redemption_code_cost'];
    		if (!is_numeric($redemption_code_cost)) {
    			$errors[] = $this->__('Redemption Code Cost field is required and it must be a number greater than zero.');
    		} else if (is_numeric($redemption_code_cost) && $redemption_code_cost < 1) {
    			$errors[] = $this->__('Redemption Code Cost field is required and it must be a number greater than zero.');
    		}
    	} else {
    		$errors[] = $this->__('Redemption Code Cost field is required and it must be a number greater than zero.');
    	}
    	if (isset($giftCardParams['redemption_cost']) && $giftCardParams['redemption_cost']) {
    		$rvGifcardBalance = $giftCardParams['redemption_cost'];
    		if (!is_numeric($rvGifcardBalance)) {
    			$errors[] = $this->__('Purchase Price field is required and it must be a number greater than zero.');
    		} else if (is_numeric($rvGifcardBalance) && $rvGifcardBalance < 1) {
    			$errors[] = $this->__('Purchase Price field is required and it must be a number greater than zero.');
    		}
    	} else {
    		$errors[] = $this->__('Purchase Price field is required and it must be a number and greater than zero.');
    	}
    	
    	if (count($errors) > 0) {
    		$strErrors = '<div><strong>' . $this->__("RV Giftcard was not created. Please check the following rules and create again:") . '</strong></div>';
    		foreach ($errors as $error) {
    			$strErrors .= $error . '<br/>';
    		}
    		Mage::getSingleton('adminhtml/session')->addError($strErrors);
    		$this->_redirect('*/*/');
    	} else {
    		$getGiftcardParams = array(
    				'prefix_code' => $rvGifcardPrefix,
    				'how_many' => $rvGifcardCount,
    				'redemption_cost' => $rvGifcardBalance);
    		Mage::getSingleton('core/session')->setGiftcardParams($getGiftcardParams);
    	}
	//See how many to add
	//Save the insert ids for followup CSV
	$newIds = array();
	for ($inx = 0; $inx < $rvGifcardCount; $inx++)
	{
	    $rvGiftcardModel = Mage::getModel('aw_giftcard/giftcard');
	    $rvGiftcardModel->setRvPrefix();
	    $cardData['expire_at'] = null;
	    $cardData['balance'] = $rvGifcardBalance;
	    $cardData['redemption_code_cost'] = $redemption_code_cost;
	    $cardData['website_id'] = 1;
	    $cardData['status'] = 1;
	    $cardData['state'] = 1;
	    try {
		$rvGiftcardModel->addData($cardData)->save();
		$newId = $rvGiftcardModel->getId();
		$newIds[] = $newId;
	    } catch (Exception $e) {
		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
	    }
	}

/*
	Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__('%d giftcard(s) have been successfully created', count($newIds))
            );
*/

	if(!empty($newIds))
	{
	    //Create CSV
	    $currDateTime = date('Y-m-d_H-i-s');
	    $filename = 'RV-Giftcard'.$currDateTime.'.csv';
            $helper =  Mage::helper('aw_giftcard/rvcsv');
	    $helper->setList($newIds);
	    $content = $helper->generateGcList();
	    $this->_prepareDownloadResponse($filename, $content);
	}

	//$this->_redirect('*/*/');
    }

    public function editAction()
    {
        $giftcard = $this->_initGiftCard();
        $this->_initAction();
        $this->_title($this->__('Manage Gift Cards'));

        if ($giftcard->getId()) {
            $breadcrumbTitle = $breadcrumbLabel = $this->__('Edit Gift Card');
        } else {
            $breadcrumbTitle = $breadcrumbLabel = $this->__('New Gift Card');
        }

        $this
            ->_title($breadcrumbTitle)
            ->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle)
            ->renderLayout()
        ;
    }

    public function saveAction()
    {
        if ($formData = $this->getRequest()->getPost()) {
            if ($this->getRequest()->getPost('expire_at', null)) {
                $formData = $this->_filterDates($formData, array('expire_at'));
            } else {
                $formData['expire_at'] = null;
            }

            $giftcardModel = $this->_initGiftCard();
            try {
                $giftcardModel
                    ->addData($formData)
                    ->save()
                ;
                Mage::getSingleton('adminhtml/session')->setFormData(null);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit',
                        array(
                            'id'  => $giftcardModel->getId(),
                            'tab' => $this->getRequest()->getParam('tab', null)
                        )
                    );
                    return;
                }
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($formData);
                $this->_redirect(
                    '*/*/edit',
                    array(
                        'id'  => $giftcardModel->getId(),
                        'tab' => $this->getRequest()->getParam('tab', null)
                    )
                );
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function gridHistoryAction()
    {
        $giftcardModel = $this->_initGiftCard();
        if (null === $giftcardModel->getId()) {
            return;
        }
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('aw_giftcard/adminhtml_giftcard_edit_tab_history')->toHtml()
        );
    }

    public function exportCsvAction()
    {
        $fileName = 'aw_giftcard.csv';
        $content = $this->getLayout()->createBlock('aw_giftcard/adminhtml_giftcard_grid')
            ->getCsvFile()
        ;
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName = 'aw_giftcard.xml';
        $content = $this->getLayout()->createBlock('aw_giftcard/adminhtml_giftcard_grid')
            ->getExcelFile()
        ;
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function massStatusAction()
    {
        $giftcardIds = $this->getRequest()->getParam('giftcard', null);
        $status = $this->getRequest()->getParam('status', null);
        try {
            if (!is_array($giftcardIds)) {
                throw new Mage_Core_Exception($this->__('Invalid giftcard ids'));
            }

            if (null === $status) {
                throw new Mage_Core_Exception($this->__('Invalid status value'));
            }
            foreach ($giftcardIds as $id) {
                Mage::getSingleton('aw_giftcard/giftcard')
                    ->load($id)
                    ->setStatus($status)
                    ->save()
                ;
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__('%d giftcard(s) have been successfully updated', count($giftcardIds))
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    public function massDeleteAction()
    {
        $giftcardIds = $this->getRequest()->getParam('giftcard', null);
        try {
            if (!is_array($giftcardIds)) {
                throw new Mage_Core_Exception($this->__('Invalid giftcard ids'));
            }

            foreach ($giftcardIds as $id) {
                Mage::getSingleton('aw_giftcard/giftcard')
                    ->load($id)
                    ->delete()
                ;
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__('%d giftcard(s) have been successfully deleted', count($giftcardIds))
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    public function deleteAction()
    {
        $giftcardModel = $this->_initGiftCard();
        try {
            $giftcardModel->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__('Giftcard have been successfully deleted')
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }
}
