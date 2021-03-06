<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 * 
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the 
 * provided Sweet Tooth License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time Sweet Tooth spent 
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension. 
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Test Controller used for testing purposes ONLY!
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 *
 */
class TBT_Rewards_Manage_Debug_ProductController extends Mage_Adminhtml_Controller_Action 
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    public function indexAction() 
    {
        $this->getResponse()->setBody("This is the test controller that should be used for test purposes only!");
    }
	
    public function isAdminModeAction() 
    {
        $content = "Admin is=<pre>" . print_r ( Mage::getSingleton ( 'adminhtml/session_quote' )->getData (), true ) . "</pre><BR />";
    
        if ($this->_getSess ()->isAdminMode ()) {
            $content .= "Is admin";
        } else {
            $content .= "not admin mode";
        }
        
        $this->getResponse()->setBody($content);
    }
	
    public function optAction()
    {
        $product = $this->_getProduct ( 4 ); // notebook;
        $content = "Loaded product with name [{$product->getName()}] with SKU [{$product->getSku()}]<BR />";
        
        $points = $this->_genPointsModel ( $product );
        $content .= "Points Available for Award is [{$points->getRendering()}] <BR />";
        $content .= "Price of loaded product is  [{$product->getFinalPrice()}] <BR />";
        
        $points_optimized_price = $product->getRewardAdjustedPrice ();
        $content .= "Rewards Optimized Price Is  [{$points_optimized_price['points_price']}] with [{$points_optimized_price['points_string']}]<BR />";
        $this->getResponse()->setBody($content);
    }
	
    public function checkPointsBlockAction()
    {
        $content = $this->createPointsModel ()->setPoints ( 1, 121 ) . " (using points model) <Br/ >";
        $content .= Mage::getModel ( 'rewards/points' ) . " (using points model) <Br/ >";
        $content .= Mage::helper ( 'rewards' )->getPointsString ( array (1 => 121 ) ) . " (using rewards helper)";
        
        $this->getResponse()->setBody($content);
    }
	
    /**
     * Gets a points model model
     * @return TBT_Rewards_Model_Points
     */
    public function createPointsModel() 
    {
        $m = Mage::getModel ( 'rewards/points' );
        return $m;
    }
	
    /**
     * Fetches a points model from a product.
     * TODO: Ideally the product should reutnr the points model.
     *
     * @param TBT_Rewards_Model_Catalog_Product $product
     * @return TBT_Rewards_Model_Points
     */
    public function _genPointsModel($product) 
    {
        $points = Mage::getModel ( 'rewards/points' );
        $points->add ( $product->getEarnablePoints () );
        return $points;
    }

    /**
     * gets a product
     *
     * @param integer $id
     * @return TBT_Rewards_Model_Catalog_Product
     */
    public function _getProduct($id) 
    {
        return Mage::getModel ( 'rewards/catalog_product' )->load ( $id );
    }

    /**
     * Fetches the Jay rewards customer model.
     * @return TBT_Rewards_Model_Customer
     */
    public function _getJay() 
    {
        return Mage::getModel ( 'rewards/customer' )->load ( 1 );
    }

    /**
     * Fetches the rewards session
     * @return TBT_Rewards_Model_Session
     */
    public function _getSess() 
    {
        return Mage::getSingleton ( 'rewards/session' );
    }

    /**
     * Gets the default rewards helper
     * @return TBT_Rewards_Helper_Data
     */
    public function _getHelp() 
    {
        return Mage::helper ( 'rewards' );
    }
}

