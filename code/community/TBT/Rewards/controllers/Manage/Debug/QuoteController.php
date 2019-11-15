<?php

/**
 * Test Controller used for testing purposes ONLY!
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 *
 */
class TBT_Rewards_Manage_Debug_QuoteController extends Mage_Adminhtml_Controller_Action
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

