<?php
/**
 * Collinsharper/Reels/controllers/IndexController.php
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
 * Collinsharper_Reels_IndexController
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
class Collinsharper_Reels_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Default Index Action
     *
     * @return  void
     */
    public function indexAction()
    {
        $this->loadLayout();

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        $breadcrumbs->addCrumb('home', array(
                'label'=> Mage::helper('chreels')->__('HOME'),
                'title' => Mage::helper('chreels')->__('HOME'),
                'link' => Mage::getBaseUrl()
            )
        );

        $this->renderLayout();
    }

    public function saveCustomerReelsBinaryAction() {
        /*
         *
         *
        curl \
          -F "customer_id=177064" \
          -F "platform=iOS" \
          -F "final_reel_name=Rudie's Test reel on July 24" \
          -F "final_reel_file=@rudie_test.jpg" \
          https://www.image3d.com/retroviewer/chreels/index/saveCustomerReelsBinary

        // not working
        curl --request POST \
          --data "customer_id=177064&final_reel_name='Rudie test Reel'" \
          --data-binary thumb@download.png \
          https://www.image3d.com/retroviewer/chreels/index/saveCustomerReelsBinary
         */

        ////////////////////
        // Input Param
        ////////////////////
        $customerId = Mage::app ()->getRequest ()->getParam ( 'customer_id' );
        $finalReelName = Mage::app ()->getRequest ()->getParam ( 'final_reel_name' );
        $entityId = Mage::app ()->getRequest ()->getParam ( 'entity_id' );
        $status = 10; // Completed Reel status
        //$status = Collinsharper_Reels_Model_Reels::COMPLETE_STATUS; // Completed Reel status
        $finalReelFile = '';
        $thumb = '';
        $platform = Mage::app ()->getRequest ()->getParam ( 'platform' );
        if (!$platform || $platform == '' || is_null($platform)) $platform = 'iOS';

        if ($customerId) {
            $reel = Mage::getModel('chreels/reels');
            $reelData = array();

            if ($entityId) {
                $reelData['entity_id'] = $entityId;
            } else {
                $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                $reelData['imported_id'] = 0;
                $reelData['customer_id'] = $customerId;
                $reelData['anonymous_email'] = null;
                $reelData['reel_name'] = $finalReelName;
                $reelData['reel_data'] = '';
                $reelData['status'] = $status;
                $reelData['is_public'] = 0;
                $reelData['public_reel_path'] = null;
                $reelData['final_reel_file'] = $finalReelFile;
                $reelData['thumb'] = $thumb;
                $reelData['file_status'] = 0;
                $reelData['is_ordered'] = 0;
                $reelData['created_at'] = $time;
                $reelData['updated_at'] = $time;
                $reelData['viewed_at'] = $time;
                $reelData['platform'] = $platform;

                try {

                    $reel->setData($reelData);
                    $reel->save();

                    $entityId = $reel->getId();
                    $reelData['entity_id'] = $entityId;

                } catch (Exception $e) {
                    mage::helper('chimage3d/image')->log(__METHOD__ . __LINE__ . $e->getMessage());
                    echo json_encode ( array (
                        'code' => 1,
                        'msg' => $e->getMessage()
                    ) ); exit;
                }
            }


            ////////////////////
            // Save image file
            ////////////////////
            $mediaDir = Mage::getBaseDir('media');
            $imageDir = 'reel_builder/complete/' . $customerId . '/';
            $path = $mediaDir . DS . $imageDir;

            if (!empty($_FILES['final_reel_file']['name'])) {
                try {
                    $uploader = new Varien_File_Uploader('final_reel_file');
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(false);
                    $uploader->save($path, $_FILES['final_reel_file']['name']);
                    $finalReelFile = $imageDir . $uploader->getUploadedFileName();

                    $reelImagePath = $mediaDir . DS . $finalReelFile;

                    // Add QR code
                    if (file_exists($reelImagePath)) {
                        $background = imagecreatefromstring (file_get_contents($reelImagePath));

                        $qrConfiguration = array(
                            'QR' => array(
                                'rotate' => 45,
                                'x' => 4142 + 35 - 80, //(1761+260+50)*2,
                                'y' => 7888 + 180, //(3489+455)*2,
                            ),
                        );
                        $code_params = array('text' => $entityId,
                            'backgroundColor' => '#FFFFFF',
                            'foreColor' => '#000000',
                            'padding' => 1,  //array(10,5,10,5),
                            'moduleSize' => 19
                            //'moduleSize' => 21
                            //'moduleSize' => 23
                            //'moduleSize' => 8
                        );

                        $renderer_params = array('imageType' => 'png', 'sendResult' => false);

                        // Generate QR code image
                        $qrCode = Zend_Matrixcode::render('qrcode', $code_params, 'image', $renderer_params);

                        $qrBgColor = imageColorAllocateAlpha($qrCode, 0, 0, 0, 127);
                        $qrCode = imagerotate($qrCode, $qrConfiguration['QR']['rotate'], $qrBgColor);
                        $qrWidth = imagesx($qrCode);
                        $qrHeight = imagesy($qrCode);
                        //imagejpeg($qrCode, $path . 'qr_' . $entityId . '.jpg');

                        // Add QR image to Reel Image
                        imagecopy($background, $qrCode, $qrConfiguration['QR']['x'], $qrConfiguration['QR']['y'], 0, 0, $qrWidth, $qrHeight);

                        // Rotate completed Reel Image
                        $background = imagerotate($background, 90, 0);

                        // Save completed Reel Image
                        imagejpeg($background, $reelImagePath);
                        imagedestroy($qrCode);
                    }


                    // Generate & Upload thumbnail image
                    $thumbImageMaxWidth = 2183;
                    $thumbImageMaxHeight = 2183;
                    $thumb = $imageDir . 'thumb_' . $uploader->getUploadedFileName();
                    try {
                        if (file_exists($reelImagePath)) {
                            $imageObj = new Varien_Image($reelImagePath);
                            $imageWidth = $imageObj->getOriginalWidth();
                            $imageHeight = $imageObj->getOriginalHeight();

                            if($imageWidth >= $imageHeight) {
                                $thumbImageWidth = min($imageWidth, $thumbImageMaxWidth);
                                $thumbImageHeight = $imageHeight * ($thumbImageWidth/$imageWidth);
                            } else {
                                $thumbImageHeight = min($imageHeight, $thumbImageMaxHeight);
                                $thumbImageWidth = $imageWidth * ($thumbImageHeight/$imageHeight);
                            }

                            $imageObj->constrainOnly(TRUE);
                            $imageObj->keepAspectRatio(TRUE);
                            $imageObj->keepFrame(FALSE);
                            $imageObj->resize($thumbImageWidth, $thumbImageHeight);
                            $imageObj->save($mediaDir . DS . $thumb);

                            // Update thumbnail image for upright
                            $background = imagecreatefromstring (file_get_contents($mediaDir . DS . $thumb));
                            $background = imagerotate($background, -90, 0);
                            imagejpeg($background, $mediaDir . DS . $thumb);

                        }

                    } catch (Exception $e) {
                        mage::helper('chimage3d/image')->log(__METHOD__ . __LINE__ . $e->getMessage());
                        echo $e->getMessage(); exit;
                    }
                } catch (Exception $e) {
                    mage::helper('chimage3d/image')->log(__METHOD__ . __LINE__ . $e->getMessage());
                    echo $e->getMessage(); exit;
                }
            }


            ////////////////////
            // Save in Database
            ////////////////////
            $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

            $reelData['imported_id'] = 0;
            $reelData['customer_id'] = $customerId;
            $reelData['anonymous_email'] = null;
            $reelData['reel_name'] = $finalReelName;
            $reelData['reel_data'] = '';
            $reelData['status'] = $status;
            $reelData['is_public'] = 0;
            $reelData['public_reel_path'] = null;
            $reelData['final_reel_file'] = $finalReelFile;
            $reelData['thumb'] = $thumb;
            $reelData['file_status'] = 0;
            $reelData['is_ordered'] = 0;
            $reelData['created_at'] = $time;
            $reelData['updated_at'] = $time;
            $reelData['viewed_at'] = $time;
            $reelData['platform'] = $platform;

            try {

                $reel->setData($reelData);
                $reel->save();

                $entityId = $reel->getId();

                echo json_encode ( array (
                    'code' => 0,
                    'msg' => 'Reel added/updated. Reel id: ' . $entityId,
                    'reel_id' => (int)$entityId
                ) );

            } catch (Exception $e) {
                mage::helper('chimage3d/image')->log(__METHOD__ . __LINE__ . $e->getMessage());
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $e->getMessage()
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => 'Customer ID not existing'
            ) );
        }


    }

    public function saveCustomerReelsAction() {
        /*
         * Add new reel

        curl --request POST \
  --data "customer_id=177064&final_reel_name='Rudie test Reel'&platform=iOS&final_reel_file='data:image/png;base64'" \
  https://www.image3d.com/retroviewer/chreels/index/saveCustomerReels

         */

        /*
         * Update existing reel

        curl --request POST \
  --data "entity_id=1023122&customer_id=177064&final_reel_name='Rudie test Reel'&platform=iOS&final_reel_file='data:image/png;base64" \
  https://www.image3d.com/retroviewer/chreels/index/saveCustomerReels

         */

        ////////////////////
        // Input Param
        ////////////////////
        $customerId = Mage::app ()->getRequest ()->getParam ( 'customer_id' );
        $finalReelName = Mage::app ()->getRequest ()->getParam ( 'final_reel_name' );
        $base64FinalReelImage = Mage::app ()->getRequest ()->getParam ( 'final_reel_file' );
        $base64FinalReelImageThumb = Mage::app ()->getRequest ()->getParam ( 'thumb' );
        $entityId = Mage::app ()->getRequest ()->getParam ( 'entity_id' );
        $platform = Mage::app ()->getRequest ()->getParam ( 'platform' );
        if (!$platform || $platform == '' || is_null($platform)) $platform = 'iOS';

//        $customerId = 177064; // Rudie's customer id
//        $finalReelName = "Rudie's test Reel";

        $status = 10; // Completed Reel status

        if ($customerId) {
            $reel = Mage::getModel('chreels/reels');
            $reelData = array();

            if ($entityId) {
                $reelData['entity_id'] = $entityId;
            } else {
                $collection = $reel
                    ->getCollection()
                    ->setOrder('entity_id', 'desc');
                $collection->getSelect()->limit(1);
                $entityId = $collection->getLastItem()->getId();

                $entityId++;
            }

            $finalReelFile = $this->saveBase64ReelImage($base64FinalReelImage, $customerId, $entityId, false);
            $thumb = $this->saveBase64ReelImage($base64FinalReelImageThumb, $customerId, $entityId, true);
            $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

            $reelData['imported_id'] = 0;
            $reelData['customer_id'] = $customerId;
            $reelData['anonymous_email'] = null;
            $reelData['reel_name'] = $finalReelName;
            $reelData['reel_data'] = '';
            $reelData['status'] = $status;
            $reelData['is_public'] = 0;
            $reelData['public_reel_path'] = null;
            $reelData['final_reel_file'] = $finalReelFile;
            $reelData['thumb'] = $thumb;
            $reelData['file_status'] = 0;
            $reelData['is_ordered'] = 0;
            $reelData['created_at'] = $time;
            $reelData['updated_at'] = $time;
            $reelData['viewed_at'] = $time;
            $reelData['platform'] = $platform;

            try {

                $reel->setData($reelData);
                $reel->save();

                echo json_encode(array(
                    'code' => 0,
                    'msg' => 'Reel added/updated. Reel id: ' . $entityId
                ));

            } catch (Exception $e) {
                echo json_encode(array(
                    'code' => 1,
                    'msg' => $e->getMessage()
                ));
            }
        }


    }

    public function saveBase64ReelImage($base64Image, $customerId, $entityId, $isThumb = false)
    {
        $mediaDir = Mage::getBaseDir('media');
        $imageDir = 'reel_builder/complete/' . $customerId . '/';
        if ($isThumb) {
            $fileName = $entityId . '_thumb.jpg';
        } else {
            $fileName = $entityId . '.jpg';
        }
        $imageFilePath = $imageDir . $fileName;

        $base64Image = trim($base64Image);
        $base64Image = str_replace('data:image/png;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpg;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/gif;base64,', '', $base64Image);
        $base64Image = str_replace(' ', '+', $base64Image);

        $imageData = base64_decode($base64Image);
        file_put_contents($mediaDir . $imageFilePath, $imageData);

        return $imageFilePath;

    }

    public function getUserReelsAction() {
        /*
        curl --request POST  --data 'customer_id=23030&customer_email=shane@collinsharper.com' https://www.image3d.com/retroviewer/chreels/index/getUserReels
         */
        $customerId = Mage::app ()->getRequest ()->getParam ( 'customer_id' );
        $customerEmail = Mage::app ()->getRequest ()->getParam ( 'customer_email' );

        if ( !empty($customerId) && !empty($customerEmail)) {

            $customer = Mage::getModel('customer/customer')->load($customerId);

            if ( $customer->getData('email') == $customerEmail ) {

                $data = Mage::helper('chreels')->getCustomerReelsById($customerId, false);

                echo json_encode($data->toArray());

            } else {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => 'Customer email is not matching with customer id'
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => 'Customer id & email is empty'
            ) );
        }
    }

    public function deleteUserReelsAction() {
        /*
        curl --request POST  --data 'customer_id=177064&customer_email=rwang@collinsharper.com&reel_id=1023675' https://www.image3d.com/retroviewer/chreels/index/deleteUserReels
         */
        $customerId = Mage::app ()->getRequest ()->getParam ( 'customer_id' );
        $customerEmail = Mage::app ()->getRequest ()->getParam ( 'customer_email' );
        $entityId = Mage::app ()->getRequest ()->getParam ( 'reel_id' );
        $status = Collinsharper_Reels_Model_Reels::PEND_DELETE_STATUS; // Delete pending Reel status

        if ( !empty($customerId) && !empty($customerEmail)) {

            $customer = Mage::getModel('customer/customer')->load($customerId);

            if ( $customer->getData('email') == $customerEmail ) {

                $reel = Mage::getModel('chreels/reels')->load($entityId);

                if(!$reel || !$reel->getId() || $reel->getCustomerId() != $customerId) {
                    echo json_encode ( array (
                        'code' => 1,
                        'msg' => 'Invalid Reel'
                    ) );
                } else {
                    try {
                        $reel->delete();

                        echo json_encode ( array (
                            'code' => 0,
                            'msg' => 'Reel deleted',
                            'reel_id' => $entityId
                        ) );

                    } catch (Exception $e) {
                        echo json_encode ( array (
                            'code' => 1,
                            'msg' => $e->getMessage()
                        ) );
                    }
                }

            } else {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => 'Customer email is not matching with customer id'
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => 'Customer id & email is empty'
            ) );
        }
    }

    /**
     * Get Reel information by reel_id
     */
    public function getReelByIdAction() {
        /*
        curl --request POST  --data 'reel_id=708628' https://www.image3d.com/retroviewer/chreels/index/getReelById
         */
        $reelId = Mage::app ()->getRequest ()->getParam ( 'reel_id' );

        $result = Mage::helper('chreels')->getCustomerReelByReelId($reelId, false);
        $resultData = $result->toArray();
        $reel = $resultData['items'][0];

        if ($reel) {
            echo json_encode ( array (
                'code' => 0,
                'reel_data' => $reel
            ) );
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => 'Reel ID does not exist'
            ) );
        }
    }
}
