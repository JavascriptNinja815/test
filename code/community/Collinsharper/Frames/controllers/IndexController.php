<?php
/**
 * Collinsharper/Frames/controllers/IndexController.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Frames_IndexController
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Frames_IndexController extends Mage_Core_Controller_Front_Action
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
                'label'=> Mage::helper('chframes')->__('HOME'),
                'title' => Mage::helper('chframes')->__('HOME'),
                'link' => Mage::getBaseUrl()
            )
        );

        $this->renderLayout();
    }
}
