<?php
//require_once Mage::getModuleDir('controllers', 'Mage_Customer').DS.'AccountController.php';

//class Collinsharper_Chcustomeruploads_IndexController extends Mage_Customer_AccountController
class Collinsharper_Chcustomeruploads_IndexController extends Mage_Core_Controller_Front_Action
{

    const DEFAULT_IMAGE = '/media/customer_uploads/placeholder.png';
    const DEFAULT_FAIL_IMAGE = '/media/customer_uploads/no_image_placeholder.png';
    const DEFAULT_IMAGE_PATH =  'customer_uploads';

    private function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getCustomerId();
    }

    private function verifyUserAccess($item)
    {
        return $item && $item->getCustomerId() == $this->getCustomerId();
    }

    public function indexAction()
    {
//        $this->_initLayoutMessages('customer/sesssion');
	if(!Mage::getSingleton('customer/session')->isLoggedIn()) {
		Mage::getSingleton('customer/session')->setBeforeAuthUrl('chuploads/index/');
		$this->_redirect('customer/account/login');
		return;
	}
        $this->loadLayout();
        $this->renderLayout();
    }

    public function deleteAction()
    {

        mage::log(__METHOD__ . __LINE__);

        $accessError = Mage::helper('sales')->__('No Access.');

        if(!$this->_isAdminLoggedIn() && !$this->getCustomerId()) {
            mage::log(__METHOD__ . __LINE__);
            //Mage::getSingleton('customer/session')->addError($accessError);
            Mage::getSingleton('core/session')->addError($accessError);
            $this->_redirect('*/*/');
            return;
        }

        $imgId = $this->getRequest()->getParam('img_id');

        $img = Mage::getModel('collinsharper_chcustomeruploads/upload')->load($imgId);


        if(!$imgId || !$img || !$img->getId()) {
            mage::log(__METHOD__ . __LINE__);
            //Mage::getSingleton('customer/session')->addError($accessError . '001');
            Mage::getSingleton('core/session')->addError($accessError . '001');
            $this->_redirect('*/*/');
            return;
        }


        if(!$this->_isAdminLoggedIn()) {
            if(!$this->verifyUserAccess($img)) {
                mage::log(__METHOD__ . __LINE__);
                //Mage::getSingleton('customer/session')->addError($accessError);
                Mage::getSingleton('core/session')->addError($accessError);
                $this->_redirect('*/*/');
                return;
            }
        }


        if($img) {
            $path = $img->getData('file_path');
            @unlink(BP . DS . $path);
            $img->delete();
        }

        mage::log(__METHOD__ . __LINE__);

        //Mage::getSingleton('customer/session')->addSuccess(Mage::helper('sales')->__('Image Removed.'));
        Mage::getSingleton('core/session')->addSuccess(Mage::helper('sales')->__('Image Removed.'));
        $this->_redirect('*/*/');

    }

    public function updateAction()
    {

        $allowedUploadExtensions = array('jpg', 'jpeg', 'png', 'mpo', 'pdf', 'psd', 'zip');

        if(!$this->getCustomerId()) {
            mage::log(__METHOD__ . __LINE__);
            exit;
        }

        mage::log(__METHOD__ . __LINE__ . " post " . print_r($_POST,1));
	Mage::log(print_r($this->getRequest()->getParams(),true),null,'test.log');
        $action = $this->getRequest()->getParam('action', false);
        $isAjax = $this->getRequest()->getParam('is_ajax', false);
        $id = $this->getRequest()->getParam('id', false);
        $isStereo = $this->getRequest()->getParam('stereo_upload', false);

        $response = array();
        $error = false;
        if($action == 'upload') {

            mage::log(__METHOD__ . __LINE__);
            mage::log(__METHOD__ . __LINE__ .  " FILES " . print_r($_FILES,1));

            $fileName = '';
            $originalFileName = '';

            $guid = $this->getRequest()->getParam('guid', false);
            $comment = $this->getRequest()->getParam('eleName-centerart_comment', false);
            // lets remove all the frame  images if they already were posted.
            if($isStereo && $isAjax) {

                $sql = "delete from  customer_entity_chuploads where quote_ids = ? and  ";
                if(stristr($comment,'centerart')) {
                    $sql .= " ( comment like '(centerart%' ) ";
                } else {
                    $sql .= " ( comment like '(frame_%' or comment like '(mpo_image_frame%' ) ";
                }
                Mage::getSingleton("core/resource")->getConnection("core_write")->query($sql, array($guid));

            }

            foreach($_FILES as $fileKey => $file) {
                if (isset($file['name']) && $file['name'] != '') {

                    $quoteIds = $this->getRequest()->getParam('order_ids', array());
                    $orderIds = $this->getRequest()->getParam('quote_ids', array());
                    // TODO on stereo we set a guid to use.



                    if($guid) {
                        $quoteIds[] = $guid;
                    }


                    $fileComment = $this->getRequest()->getParam($fileKey . '_comment', false);

                    mage::log(__METHOD__ .  " testinf for " . $fileKey . '_comment AND || ' . $fileComment );

                    if($isAjax && $fileComment) {
                        $comment = $fileComment;
                    }

                    if(!$guid && $this->getRequest()->getParam('attach_quote_id', false)) {
                        $quoteIds[] = Mage::helper('checkout/cart')->getQuote()->getId();
                    }

                    if(0 && $this->getRequest()->getParam('attach_order_id', false)) {
                        if(Mage::getSingleton('customer/session')->getReserveOrderId()) {
                            $orderIds[] = Mage::getSingleton('customer/session')->getReserveOrderId();
                        } else {
                            $quote = Mage::helper('checkout/cart')->getQuote();
                            $quote->reserveOrderId();
                            $orderIds[] = $quote->getData('reserved_order_id');
                            Mage::getSingleton('customer/session')->setReserveOrderId($quote->getData('reserved_order_id'));
                            mage::log(__METHOD__ . __LINE__ . " wehav e" . print_r($orderIds,1));

                        }
                    }




                    try {
                        mage::log(__METHOD__ . __LINE__);
                        $originalFileName = $fileName = $file['name'];
			$fileName = strtolower($fileName);
                        $fileExt = substr(strrchr($fileName, ".") ,1);
                        $fileNamewoe = rtrim($fileName, $fileExt);
                        $fileNamewoe  = preg_replace('/[^a-z0-9\-\_\.]+/', '_', $fileNamewoe);
                        $fileNamewoe  = str_replace('__','_', $fileNamewoe );
                        $fileName =  $fileNamewoe  . time() . '.' . $fileExt;
                        // TODO We need to actually test the files..

                        if(!in_array(strtolower($fileExt), $allowedUploadExtensions)) {
                            throw new Exception("File type not recognized");
                        }

                        $uploader = new Varien_File_Uploader($fileKey);
                        //     $uploader->setAllowedExtensions(array('doc', 'docx','pdf', 'jpg', 'png', 'zip')); //add more file types you want to allow
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setFilesDispersion(false);
                        $localPath =  DS . 'customer_uploads' . DS . 'ftp' . DS . (int)$this->getCustomerId();
                        $path = Mage::getBaseDir('media') . $localPath;
                        mage::log(__METHOD__ . __LINE__);
                        if(!is_dir($path)) {
                            mkdir($path, 0777, true);
                        }
                        mage::log(__METHOD__ . __LINE__);
                        $uploader->save($path . DS, $fileName );
                        mage::log(__METHOD__ . __LINE__);
                        mage::log(__METHOD__ . __LINE__ . " we have file ! " . $path . DS. $fileName);


                    } catch (Exception $e) {
                        mage::log(__METHOD__ . __LINE__ . " EXCEPTION we have " . $e->getMessage());

                        $error = " Exception during upload " . $e->getMessage();
                        if($isAjax) {
                            $response['error'] = $error;
                        } else {
                            //Mage::getSingleton('customer/session')->addError($error);
                            Mage::getSingleton('core/session')->addError($error);
                        }
                        $error = true;
                    }
                    // }

                    $new = Mage::getModel('collinsharper_chcustomeruploads/upload');
                    $fullPath = DS . 'media' . $localPath . DS . $fileName;
                    $date = date('Y-m-d H:m:i');
                    $new->setOrderIds(implode(',', $orderIds));
                    $new->setQuoteIds(implode(',', $quoteIds));
                    $new->setCustomerId($this->getCustomerId());
                    $new->setFilename($originalFileName);
                    $new->setFilepath($fullPath);
                  //  $new->setData('file_md5', md5_file($fullPath));
                    $new->setStatus(1);
                    $new->setCreatedAt($date);
                    $new->setUpdatedAt($date);
                    $new->setComment($comment);
                    $new->save();
                    mage::log(__METHOD__ . " fp " . $fullPath );

                }
            }

        } else if ($action == 'delete') {
            $item = Mage::getModel('collinsharper_chcustomeruploads/upload')->load($id);
            if($item && $this->verifyUserAccess($item)) {
                // some action
            }


        }
        // ajax return OK
        // $action
        // $ids
        if($isAjax) {
            $response['complete'] = $error != true;
            $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
            $this->getResponse()->setBody(json_encode($response));
            return;
        } else {
            $this->_redirect('*/*/');
        }
    }
    public function _isAdminLoggedIn()
    {
        $sesId = isset($_COOKIE['adminhtml']) ? $_COOKIE['adminhtml'] : false ;
        // TODO: this is insecure and not complete
        return $sesId !== false;
    }

    public function thumbnailAction()
    {
        $id = $this->getRequest()->getParam('id', false);
        $customerId = $this->getCustomerId();
        $item = Mage::getModel('collinsharper_chcustomeruploads/upload')->load($id);

        $imageObj = new Varien_Image(BP . DS . self::DEFAULT_FAIL_IMAGE);

        if($this->verifyUserAccess($item) || $this->_isAdminLoggedIn()) {

            $imageObj = new Varien_Image(BP . DS . self::DEFAULT_IMAGE);

            try {
                $image = $item->getFilepath();
                $imageUrl = BP . DS . $image;
                $resizedImageDir = dirname($imageUrl) . DS . 'thumbs' ;
                if(!is_dir($resizedImageDir)) {
                    mkdir($resizedImageDir, 0775, true);
                }

                $imageResized = $resizedImageDir . DS . basename($image);

                if(file_exists($imageResized)) {
                    $imageObj = new Varien_Image($imageResized);
                }

                if (!file_exists($imageResized) && file_exists($imageUrl)) {
                    $imageObj = new Varien_Image($imageUrl);
                    $imageObj->constrainOnly(TRUE);
                    $imageObj->keepAspectRatio(TRUE);
                    $imageObj->keepFrame(FALSE);
                    $imageObj->resize(140, 140);
                    $imageObj->save($imageResized);
                }

            } catch (Exception $e) {
                // could be an EPS a zip or anything..
                mage::log(__METHOD__ . __LINE__ . " failed resize " . $e->getException() );
            }
        } else {
            mage::log(__METHOD__ . __LINE__ . " failed access " );
            exit;
        }

//        return $imageResizedPath;
        $this->getResponse()->setHeader('Content-Type', $imageObj->getMimeType());
        $this->getResponse()->setBody($imageObj->display());

    }

}
