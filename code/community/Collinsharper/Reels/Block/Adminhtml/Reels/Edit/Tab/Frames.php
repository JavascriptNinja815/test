<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Reels/Edit/Tab/Frames.php
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
 * Collinsharper_Reels_Block_Adminhtml_Reels_Edit_Tab_Frames
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
class Collinsharper_Reels_Block_Adminhtml_Reels_Edit_Tab_Frames extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function _prepareLayout()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $reel = Mage::getModel('chreels/reels')->load($id)->loadFrames();

        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

        if(!$reel->getFrames()->count()) {
            echo " <div> " . Mage::helper('chreels')->__('These are not the frames you are looking for.') . "</div>";
        }  else {

            foreach ($reel->getFrames() as $frame) {
                echo '<div class="frame_img"><div class="label">' .
                    ((int)$frame->getData("frame_index") == 0 ? Mage::helper('chreels')->__("CENTER") : $frame->getData("frame_index"))
                    . '</div><div class="file"><img src="' . $baseUrl . $frame->getData('thumb_file') . '" name="' . $reel->getData('reel_name') . '" class="frame_image" /></div></div><br />';
            }

        }

        return parent::_prepareLayout();
    }

}