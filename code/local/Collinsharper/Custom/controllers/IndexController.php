<?php
require_once Mage::getModuleDir('controllers', 'Collinsharper_Chcustomeruploads').DS.'IndexController.php';

//class Collinsharper_Chcustomeruploads_IndexController extends Mage_Customer_AccountController
class Collinsharper_Custom_IndexController extends Collinsharper_Chcustomeruploads_IndexController
{
    public function viewshareAction() {

        $reelId = $this->getRequest()->getParam('reel_id', -1);
        $reel = Mage::getModel('chreels/reels');
        $reel->load($reelId);
        if (!$reel->getId() || !$reel->getIsPublic()) {
            $this->norouteAction();
            return;
        }

        $this->getLayout()->getUpdate()->addHandle('default');
        $this->addActionLayoutHandles();
        if ($this->getRequest()->getParam('noframe', false)) {
            $this->getLayout()->getUpdate()->addHandle('reelbuilder_index_viewshare_no_frame');
        }
        $this->loadLayoutUpdates();
        $this->generateLayoutXml();
        $this->generateLayoutBlocks();
        $this->_isLayoutLoaded = true;

        $this->loadLayout();

        $imgReel = BP . '/media/' . $reel->getData('final_reel_file');
        $pathDetails = pathinfo($imgReel);
        $imgReelFinalUrl = $pathDetails['dirname'] . "/" . $pathDetails['filename'] . "_shared.jpg";
        $imgReelFinalUrlCleaned = Mage::getBaseUrl().strstr($imgReelFinalUrl, 'media');

        $headHtml = '<script type="text/javascript">var switchTo5x=true;</script>';
        $headHtml .= '<script type="text/javascript" src="https://ws.sharethis.com/button/buttons.js"></script>';
        $headHtml .= '<script type="text/javascript">stLight.options({publisher: "1c9128ab-d906-44c0-b14d-2473364e899b", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>';

        $st_image = $imgReelFinalUrlCleaned;
        $st_title = "Image3D RetroViewer";
        $st_summary = "Look at my awesome RetroViewer reel!";
        $st_via = 'image3dusa';
        $st_url = Mage::getBaseUrl() . "reelbuilder/index/viewshare?reel_id=" . $reel->getId();

        $headHtml .= '<meta property="og:title" content="' . $st_title . '"/>';
        $headHtml .= '<meta property="og:type" content="article"/>';
        $headHtml .= '<meta property="og:url" content="' . $st_url . '"/>';
        $headHtml .= '<meta property="og:image" content="' . $st_image . '"/>';
        $headHtml .= '<meta property="og:description" content="' . $st_summary . '"/>';

        $this->getLayout()->getBlock('share_head')->setText($headHtml);
        $html = "<div id='container_share'>";
        $special = '<div><h3>' . $this->__('Share this reel?') . '</h3>';
        $special .= '<br /><img src="' .$imgReelFinalUrlCleaned . '" />';

        $special .= '<div id="social_container">
                    <span class="st_sharethis_large" displayText="ShareThis" st_url = "'.$st_url.'"></span>
                    <span st_url = "'.$st_url.'" st_via="'.$st_via.'" st_title="'.$st_title.'" st_summary="'.$st_summary.'" class="st_facebook_large" displayText="Facebook"></span>
                    <span st_url = "'.$st_url.'" st_via="'.$st_via.'" st_image="'.$st_image.'" st_title="'.$st_summary.'" st_summary="'.$st_summary.'" class="st_twitter_large" displayText="Tweet"></span>
                    <span st_url = "'.$st_url.'" st_via="'.$st_via.'" st_image="'.$st_image.'" st_title="'.$st_title.'" st_summary="'.$st_summary.'" class="st_linkedin_large" displayText="LinkedIn"></span>
                    <span st_url = "'.$st_url.'" st_via="'.$st_via.'" st_image="'.$st_image.'" st_title="'.$st_title.'" st_summary="'.$st_summary.'" class="st_pinterest_large" displayText="Pinterest"></span>
                    <span st_url = "'.$st_url.'" st_via="'.$st_via.'" st_image="'.$st_image.'" st_title="'.$st_title.'" st_summary="'.$st_summary.'" class="st_email_large" displayText="Email"></span></div>';
        $special .= Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('social-sharing-blurb')->toHtml();
        $special .= '<div class="buildown"><a href="' . Mage::getBaseUrl() . 'reelbuilder/' . '" class="button-link">' . $this->__("Build your own reel") . '</a></div>';
        $special .= '</div>';
        $html .= $special;
        $html .="</div>";

        $this->getLayout()->getBlock('share')->setText($html);
        $this->renderLayout();
        return;
    }
    private function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getCustomerId();
    }
    public function updateAction()
    {
        $allowedUploadExtensions = array('jpg', 'jpeg', 'png', 'mpo', 'pdf', 'psd', 'zip');
        if(!$this->getCustomerId()) {
            mage::log(__METHOD__ . __LINE__);
            exit;
        }

//        mage::log(__METHOD__ . __LINE__ . " post " . print_r($_POST,1));

        $action = $this->getRequest()->getParam('action', false);
        $isAjax = $this->getRequest()->getParam('is_ajax', false);
        $id = $this->getRequest()->getParam('id', false);
        $isStereo = $this->getRequest()->getParam('stereo_upload', false);
        $checkDelete = $this->getRequest()->getParam('check_delete', false);

        $response = array();
        $error = false;
        $fn = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);
	$_fn = null;

        if($fn || $action == 'upload') {
            if (!empty($_SERVER['HTTP_X_STEREO'])) {
		$isStereo = true;
	    }

            mage::log(__METHOD__ . __LINE__);
            mage::log(__METHOD__ . __LINE__ .  " FILES " . print_r($_FILES,1));
	    $comment = '';
	    foreach ($_FILES as $_FILE_KEY => $_FILE_DATA) {
		if (stristr($comment,'centerart')) {
			$comment = $this->getRequest()->getParam('centerart', '');
		} else {
			$comment = $this->getRequest()->getParam($_FILE_KEY . '_comment', '');
		}
		$fn = $_FILE_DATA['name'];
		$_fn = $_FILE_DATA['tmp_name'];
	    }

            $fileName = '';
            $originalFileName = '';

            $guid = $this->getRequest()->getParam('guid', false);
	    if (!empty($_SERVER['HTTP_X_GUID'])) {
		$guid = $_SERVER['HTTP_X_GUID'];
            }

//$response['data'][] = __LINE__;
            // lets remove all the frame  images if they already were posted.
            if($isStereo && $isAjax && $checkDelete) {

                $sql = "delete from  customer_entity_chuploads where quote_ids = ? and  ";
                if(stristr($comment,'centerart')) {
                    $sql .= " ( comment like '(centerart%' ) ";
                } else {
                    $sql .= " ( comment like '(frame_%' or comment like '(mpo_image_frame%' ) ";
                }
                Mage::getSingleton("core/resource")->getConnection("core_write")->query($sql, array($guid));

            }

            if (!empty($fn)) {
                $quoteIds = $this->getRequest()->getParam('order_ids', array());
                $orderIds = $this->getRequest()->getParam('quote_ids', array());
                // TODO on stereo we set a guid to use.
                if($guid) {
                    $quoteIds[] = $guid;
                }
                $originalFileName = $fileName = $fn;
                $fileName = strtolower($fileName);
                $fileExt = substr(strrchr($fileName, ".") ,1);
                $fileNamewoe = rtrim($fileName, $fileExt);
                $fileNamewoe  = preg_replace('/[^a-z0-9\-\_\.]+/', '_', $fileNamewoe);
                $fileNamewoe  = str_replace('__','_', $fileNamewoe );
                $fileName =  $fileNamewoe  . time() . '.' . $fileExt;
                if(!in_array(strtolower($fileExt), $allowedUploadExtensions)) {
                    throw new Exception("File type not recognized");
                }
                $localPath =  DS . 'customer_uploads' . DS . 'ftp' . DS . (int)$this->getCustomerId();
                $path = Mage::getBaseDir('media') . $localPath;
               mage::log(__METHOD__ . __LINE__);

                if(!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                try {
// KL: It is not a true ajax upload anymore
//                    file_put_contents($path . DS . $fileName, file_get_contents('php://input'));
			move_uploaded_file($_fn, $path . DS . $fileName);
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
                $new = Mage::getModel('collinsharper_chcustomeruploads/upload');
                $fullPath = DS . 'media' . $localPath . DS . $fileName;
                $date = date('Y-m-d H:m:i');
                $new->setOrderIds(implode(',', $orderIds));
                $new->setQuoteIds(implode(',', $quoteIds));
                $new->setCustomerId($this->getCustomerId());
                $new->setFilename($originalFileName);
                $new->setFilepath($fullPath);
                $new->setStatus(1);
                $new->setCreatedAt($date);
                $new->setUpdatedAt($date);
                $new->setComment($comment);
                $new->save();
                mage::log(__METHOD__ . " fp " . $fullPath );
            }
/*
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
*/
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
}
