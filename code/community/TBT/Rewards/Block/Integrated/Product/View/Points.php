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
 * Product View Points
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Integrated_Product_View_Points extends TBT_Rewards_Block_Product_View_Points {


    
    protected function _prepareLayout()
    {   
        /*
         * For compatibility with MC 1.9+ and third party modules,
         * 
         * Look for the product.info block & search for one of it's children aliased as "other".
         * If such a child doesn't exist, create it, then re-parent and prepend this block under "other".
         * If it does exist, make sure it's one that can output our block ('core/text_list').
         * If the product.info template doesn't output the "other" block, then we fall back on the default way this block was supposed to render.
         * */
		if (Mage::helper('rewards/theme')->getPackageName() === "rwd"){		    	
	        $productInfoBlock = $this->getLayout()->getBlock('product.info');
	        if ($productInfoBlock) {
	            $otherBlock = $productInfoBlock->getChild('other');
	            if (!$otherBlock){	            	
	                $otherBlock = $this->getLayout()->createBlock('core/text_list', 'other')->append($this);	                
	                $productInfoBlock->append($otherBlock);
	            } else if ($otherBlock instanceof Mage_Core_Block_Text_List) {
	                $newBlock = $this->getLayout()->createBlock('core/text_list', 'rewards.integrated.product.view.points.output')->append($this);
	                $otherBlock->insert($newBlock, '', false);
	            }
	            
	            // For some reason when cache is enabled, this block starts acting up.
	            // Will disable cache for this block on the RWD theme	            
				$this->setCacheLifetime(null);	            	            
	        }
		}
        
        return parent::_prepareLayout();;    
    }
    
    protected function _toHtml() {
        if(Mage::getStoreConfigFlag('rewards/autointegration/product_view_page_product_points')) {
            return parent::_toHtml();
        } else {
            return "";
        }
    }
}

