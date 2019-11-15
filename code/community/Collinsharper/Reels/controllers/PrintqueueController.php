<?php
/**
 * Collinsharper/Reels/controllers/PrintqueueController.php
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
 * Collinsharper_Reels_PrintqueueController
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
class Collinsharper_Reels_PrintqueueController extends Mage_Core_Controller_Front_Action
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
}
