<?php
class Collinsharper_Reelbuilder_IndexController extends Mage_Core_Controller_Front_Action
{

	public function IndexAction()
	{

		ini_set('upload_max_filesize', '5M');
		// the server uses the canvas to render it.
		$helper = new Collinsharper_Image3d_Helper_Data;
		$serverCallbackSkipAuth = $helper->isServerCallback();

		if($serverCallbackSkipAuth) {
			Mage::getSingleton('core/session')->setIsServerCallBack(true);
		}

		if (!$serverCallbackSkipAuth && !Mage::helper('customer')->isLoggedIn()) {
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account/login'));
			Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());
			return;
		}

		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Reel Builder"));
		/*	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
              $breadcrumbs->addCrumb("home", array(
                        "label" => $this->__("Home Page"),
                        "title" => $this->__("Home Page"),
                        "link"  => Mage::getBaseUrl()
                   ));

              $breadcrumbs->addCrumb("reel builder", array(
                        "label" => $this->__("Reel Builder"),
                        "title" => $this->__("Reel Builder")
                   ));
        */
		$this->renderLayout();

	}

	public function deleteAction()
	{
		$reelId = (int)(isset($_GET['reel_id']) ? $_GET['reel_id'] : -1);
		$customerId =  Mage::getSingleton('customer/session')->getCustomer()->getId();
		if(!$reelId || $reelId <= 0 || !$customerId) {
			Mage::getSingleton('customer/session')->addError('Invalid Reel.');
			$this->_redirect('customer/account/');
			return;
		}
		$reel = Mage::getModel('chreels/reels');
		$reel->load($reelId);
		if(!$reel || !$reel->getId() || $reel->getCustomerId() != $customerId) {
			Mage::getSingleton('customer/session')->addError('Invalid Reel.');
			$this->_redirect('customer/account/');
			return;
		}
		//manage file removal - or queue the files to be checked - duplicate reels mIGHT share files?
		//Remove commented out call to Mage::helper('chreels/cleaner')->cleanReelFiles
		//in /app/code/community/Collinsharper/Reels/Model/Reels.php

		$reel->delete();
		Mage::getSingleton('customer/session')->addSuccess('Reel deleted.');
		$this->_redirect('customer/account/');
		return;

	}

	public function shareAction() {
		$this->loadLayout();

        $reelId = $this->getRequest()->getParam('reel_id', -1);
		$customerId =  Mage::getSingleton('customer/session')->getCustomer()->getId();
		if(!$reelId || $reelId <= 0 || !$customerId) {
			Mage::getSingleton('customer/session')->addError('Invalid Reel.');
			$this->_redirect('customer/account/');
			return;
		}
		$reel = Mage::getModel('chreels/reels');
		$reel->load($reelId);
		if(!$reel || !$reel->getId() || $reel->getCustomerId() != $customerId) {
			Mage::getSingleton('customer/session')->addError('Invalid Reel.');
			$this->_redirect('customer/account/');
			return;
		}

		$isPublic = $reel->getData("is_public");

		if ($isPublic == 1) {
			$reel->setData('is_public',0);
			$reel->save();
			//Mage::getSingleton('customer/session')->addSuccess('Reel successfully unshared.');
			$response = new Varien_Object();
			$response->setData('success', true);
			$response->setData('message', $this->__('Reel successfully unshared.'));
			$response->setData('shared_reel', '');

			$this->getResponse()->setBody($response->toJson());
			return;
		} else {

			ini_set('memory_limit', '1024M');
			$imgReel = BP . '/media/' . $reel->getData('final_reel_file');
			$pathDetails = pathinfo($imgReel);
			$imgReelFinalUrl = $pathDetails['dirname'] . "/" . $pathDetails['filename'] . "_shared.jpg";

			//BC: we don't wan to run this like crazy, putting file exists check
			if ( !file_exists ( $imgReelFinalUrl ) ) {
				$imgTemplate = BP ."/media/reel_builder_templates/share_template.png";
				$im = imagecreatefromjpeg($imgReel);
				$imr = imagerotate($im, 270, 0);
				$im2 = imagecreatefrompng($imgTemplate);
				$destination = imagecreatetruecolor(imagesx($im2), imagesy($im2));
				$trans = imagecolorallocatealpha($destination, 0, 0, 0, 127);
				for ($x = 0; $x < imagesx($im2); $x++) {
					for ($y = 0; $y < imagesy($im2); $y++) {
						imagesetpixel($destination, $x, $y, $trans);
					}
				}
				$placementX = 1110;
				$placementY = 75;
				$destinationWidth = 1080;
				$destinationHeight = $destinationWidth;



				imagealphablending( $im2, false );
				imagesavealpha( $im2, true );
				imagealphablending( $destination, true);
				imagesavealpha( $destination, false );


				imagecopyresized($destination, $imr, $placementX, $placementY, 0, 0, $destinationWidth, $destinationHeight, imagesx($imr), imagesy($imr));
				imagecopy($destination, $im2, 0, 0, 0, 0, imagesx($im2), imagesy($im2));
				imagealphablending( $destination, false );
				imagesavealpha( $destination, true );
				imagejpeg($destination,$imgReelFinalUrl);


				imagedestroy($im);
				imagedestroy($im2);
				imagedestroy($imr);
				imagedestroy($destination);
			} else {
				//we are good, file is already created.
			}


			//to add exception handling

			$reel->setData('is_public',1);
			$reel->save();
			//Mage::getSingleton('customer/session')->addSuccess('Reel successfully shared.');

			$imgReelFinalUrlCleaned = Mage::getBaseUrl().strstr($imgReelFinalUrl, 'media');
			$response = new Varien_Object();
			$response->setData('success', true);
			$response->setData('message', $this->__('Reel successfully shared.'));
			$response->setData('shared_reel', $imgReelFinalUrlCleaned);

			/*
			$html = "<html><head>";
			$html .= '<link rel="stylesheet" type="text/css" href="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/css/styles.css" media="all" />';
			$html .= '<script type="text/javascript" src="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/js/lib/jquery-1.10.2.min.js"></script>';
			$html .= '<script type="text/javascript" src="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/js/jquery-noconflict.js"></script>';
			$html .= '<script type="text/javascript" src="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/js/lib/jquery-ui.min.js"></script>';
			$html .= '<script type="text/javascript">var switchTo5x=true;</script>';
			$html .= '<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>';
			$html .= '<script type="text/javascript">stLight.options({publisher: "1c9128ab-d906-44c0-b14d-2473364e899b", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>';
			$html .= "</head><body class='cnt_body'><div id='container_share'>";


			$special = '<div><h3>' . $this->__('Share this reel?') . '</h3>';
			$special .= '<br /><img src="' .$imgReelFinalUrlCleaned . '" />';
			$special .= '<div id="social_container"><span class="st_sharethis_large" displayText="ShareThis"></span><span class="st_facebook_large" displayText="Facebook"></span><span class="st_twitter_large" displayText="Tweet"></span><span class="st_linkedin_large" displayText="LinkedIn"></span><span class="st_pinterest_large" displayText="Pinterest"></span><span class="st_email_large" displayText="Email"></span></div>';
			$special .= '</div>';

			$html .= $special;

			$html .="</div></body></html>";

			//return $html;
			$this->getResponse()->setBody($html);
			return;
			*/

			$this->getResponse()->setBody($response->toJson());
			return;

		}


		$this->_redirect('customer/account/');
		return;
	}




	public function viewshareAction() {
		$this->loadLayout();
		$reelId = (int)(isset($_REQUEST['reel_id']) ? $_REQUEST['reel_id'] : -1);


		$reel = Mage::getModel('chreels/reels');
		$reel->load($reelId);




			$imgReel = BP . '/media/' . $reel->getData('final_reel_file');
 			$pathDetails = pathinfo($imgReel);
			$imgReelFinalUrl = $pathDetails['dirname'] . "/" . $pathDetails['filename'] . "_shared.jpg";

			$imgReelFinalUrlCleaned = Mage::getBaseUrl().strstr($imgReelFinalUrl, 'media');

			$html = "<html><head>";
			$html .= '<link rel="stylesheet" type="text/css" href="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/css/styles.css" media="all" />';
			$html .= '<script type="text/javascript" src="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/js/lib/jquery-1.10.2.min.js"></script>';
			$html .= '<script type="text/javascript" src="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/js/jquery-noconflict.js"></script>';
			$html .= '<script type="text/javascript" src="'. Mage::getBaseUrl() . 'skin/frontend/image3d/celebrate/js/lib/jquery-ui.min.js"></script>';
			$html .= '<script type="text/javascript">var switchTo5x=true;</script>';
			$html .= '<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>';
			$html .= '<script type="text/javascript">stLight.options({publisher: "1c9128ab-d906-44c0-b14d-2473364e899b", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>';
			$html .= "</head><body class='cnt_body'><div id='container_share'>";


			$special = '<div><h3>' . $this->__('Share this reel?') . '</h3>';
			$special .= '<br /><img src="' .$imgReelFinalUrlCleaned . '" />';
			$special .= '<div id="social_container"><span class="st_sharethis_large" displayText="ShareThis"></span><span class="st_facebook_large" displayText="Facebook"></span><span class="st_twitter_large" displayText="Tweet"></span><span class="st_linkedin_large" displayText="LinkedIn"></span><span class="st_pinterest_large" displayText="Pinterest"></span><span class="st_email_large" displayText="Email"></span></div>';
			$special .= '<div class="buildown"><a href="' . Mage::getBaseUrl() . 'reelbuilder/' . '" class="button-link">' . $this->__("Build your own reel") . '</a></div>';
			$special .= '</div>';

			$html .= $special;

			$html .="</div></body></html>";

			//return $html;
			$this->getResponse()->setBody($html);
			return;

	}






	public function duplicateAction()
	{
		$reelId = (int)(isset($_GET['reel_id']) ? $_GET['reel_id'] : -1);
		$customerId =  Mage::getSingleton('customer/session')->getCustomer()->getId();
		if(!$reelId || $reelId <= 0 || !$customerId) {
			Mage::getSingleton('customer/session')->addError('Invalid Reel.');
			$this->_redirect('customer/account/');
			return;
		}
		$reel = Mage::getModel('chreels/reels');
		$reel->load($reelId);
		if(!$reel || !$reel->getId() || $reel->getCustomerId() != $customerId) {
			Mage::getSingleton('customer/session')->addError('Invalid Reel.');
			$this->_redirect('customer/account/');
			return;
		}

		$mediaPath = Mage::getBaseDir() . DS . 'media' . DS;

		$newReel = Mage::getModel('chreels/reels');
		$newReel->setData($reel->getData());
		$newReel->setData("reel_name", $reel->getData('reel_name') . ' - Copy');
		$newReel->unsId();
		$newReel->unsEntityId();
		// just not 10?
		$newReel->setData("status", 3);
		$newReel->setData("entity_id", null);


		$newReel->save();
		$newReelId = $newReel->getId();

		//Make new copies of thumb if value is set
		$thumb = $reel->getData('thumb');
		$newCompletePath = $mediaPath . 'reel_builder' . DS . 'complete' . DS . $customerId . DS;

		if($thumb && file_exists($mediaPath . $thumb)) {
			copy($mediaPath . $thumb, $newCompletePath . $newReelId . '_thumb.jpg');
			$newThumb = 'reel_builder' . DS . 'complete' . DS . $customerId . DS . $newReelId . '_thumb.jpg';
			$newReel->setData('thumb', $newThumb);
			$newReel->save();
		}

		$newLinkPath = $mediaPath . 'reel_builder' . DS . 'uploads' . DS . 'users' . DS . $customerId . DS . $newReelId . DS;
		mkdir($newLinkPath);

		$frames = Mage::getModel('chframes/frames')->getCollection();
		$frames->addFieldToFilter('reel_id', $reel->getEntityId());
		$frames->setOrder('frame_index','asc')->load();

		$linkFileList = array(
			'source_file',
			'rendered_file',
			'left_file',
			'right_file',
			'background_file',
			'text_file',
			'thumb_file');

		foreach($frames as $frame) {
			$newFrame = Mage::getModel('chframes/frames');
			//Update the record values to use the new reel id
			$newFrame->setData($frame->getData());
			//Get the old id here for cb url replacement to follow.
			$oldFrameId = $newFrame->getEntityId();
			$newFrame->unsId();
			$newFrame->unsEntityId();

			//duplicate the images as when we delete a stale reel; we might remove the images for this reel
			foreach($linkFileList as $fieldName) {
				$fieldValue = $frame->getData($fieldName);
				if($fieldValue && file_exists($mediaPath . $fieldValue)) {
					//Link the existing target to a new name
					copy($mediaPath . $fieldValue, $newLinkPath . basename($fieldValue));
				}
				$fieldValue = str_replace($reelId, $newReelId, $fieldValue);
				$newFrame->setData($fieldName, $fieldValue);
			}

			$newFrame->setData('reel_id', $newReel->getId());
			$newFrame->save();

			//The canvas json image object cb url points to the old reel. Update it here
			$newFrameId = $newFrame->getId();
			$frameData = $newFrame->getFrameData();

			$oldString = 'reel_id='.$reelId.'&frame_id='.$oldFrameId;
			$newString = 'reel_id='.$newReelId.'&frame_id='.$newFrameId;
			$frameData = str_replace($oldString, $newString, $frameData);
			$newFrame->setFrameData($frameData);
			$newFrame->save();
		}

		Mage::getSingleton('customer/session')->addSuccess('Reel Duplicated.');

		$this->_redirect('customer/account/');
		return;

	}

	public function mobileAction()
	{
		ini_set('upload_max_filesize', '5M');
		$helper = new Collinsharper_Image3d_Helper_Data;
		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Mobile Reel Builder"));
		$this->renderLayout();

	}

	public function previewAction()
	{
		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Reel Builder Preview"));
		$this->renderLayout();

	}

    /**
     * Get Reel builder Stock Images API
     */
	public function getStockImagesAction() {
        echo Mage::helper('chimage3d/stockart')->getMobileStockArt(true);
    }
}
