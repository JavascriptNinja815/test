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

class TBT_Rewards_Model_Points extends Varien_Object
{  	
    protected $points = array();
    
    /**
     * Adds points to be displayed
     * 
     * @param integer||array|| any type of rewards or Mage rule $param1 
     * : - if a rule is passed in, the points effect of the rule is added
     * : - If an integer is passed in the value is assumed to be the currency id and the second
     *     parameter is assumed to be the points amount
     * : - if an array is passed in the value is assumed to be in the format 
     *     array( $currency_id => $pointsAmount )
     * : - if an TBT_Rewards_Model_Points is passed the points from the 
     * 	   other TBT_Rewards_Model_Points will be added to this model
     * @param integer|null $param2=null	
     * : - if not null the function will assume the 
     * 	   format function($currency_id, $pointsAmount)
     * @return TBT_Rewards_Block_Points
     */
    public function add($param1, $param2 = null) 
    {
    	if ($param2 != null) {
            $currency_id = intval($param1);
            $pointsAmount = intval($param2);
            
            if(!isset($this->points[$currency_id])) {
                $this->points[$currency_id] = 0;
            }
            
            $this->points[$currency_id] += $pointsAmount;
    	} elseif (is_array($param1)) {
            $points = &$param1;
            
            foreach($points as $currency_id => $pointsAmount) {
                $this->add($currency_id, $pointsAmount);
            }
    	} elseif ($param1 instanceof TBT_Rewards_Model_Points) {
            $this->add($param1->getPoints());
    	} elseif ($param1 instanceof TBT_Rewards_Model_Salesrule_Rule
            || $param1 instanceof Mage_CatalogRule_Model_Rule
            || $param1 instanceof Mage_SalesRule_Model_Rule 
            || $param1 instanceof Varien_Object) 
        {
            $rule = &$param1;
            if($rule->getPointsCurrencyId()) {
                $this->add($rule->getPointsCurrencyId(), $rule->getPointsAmount());
            }
        } else {
            /* Do nothing since the parameters entered were incorrect... */
    	}
        
    	return $this;
    }
    
    /**
     * Sets the points for this points model.  Any previous points put into this model will be cleared.
     * 
     * @param integer||array|| any type of rewards or Mage rule $param1 
     * : - if a rule is passed in, the points effect of the rule is added
     * : - If an integer is passed in the value is assumed to be the currency id and the second
     *     parameter is assumed to be the points amount
     * : - if an array is passed in the value is assumed to be in the format 
     *     array( $currency_id => $pointsAmount )
     * @param integer|null $param2=null	
     * : if not null the function will assume the 
     * 	   format function($currency_id, $pointsAmount)
     * @return TBT_Rewards_Block_Points
     */
    public function setPoints($param1, $param2 = null) 
    {
    	$this->clear();
    	return $this->add($param1, $param2);
    }
    
    /**
     * Sets the points for this points model. Any previous points put into this model will be cleared.
     * @alias for setPoints()
     * @param integer||array|| any type of rewards or Mage rule $param1 
     * : - if a rule is passed in, the points effect of the rule is added
     * : - If an integer is passed in the value is assumed to be the currency id and the second
     *     parameter is assumed to be the points amount
     * : - if an array is passed in the value is assumed to be in the format 
     *     array( $currency_id => $pointsAmount )
     * @param integer|null $param2=null	
     * : - if not null the function will assume the 
     * 	   format function($currency_id, $pointsAmount)
     * @return TBT_Rewards_Block_Points
     */
    public function set($param1, $param2 = null) 
    {
        if ($param1 instanceof TBT_Rewards_Model_Points) {
            $param1 = $param1->getPoints();
        }
        
    	return $this->setPoints($param1, $param2);
    }
    
    /**
     * Clears out all points information stored by this model         
     * @return TBT_Rewards_Block_Points
     */
    public function clear() 
    {
    	$this->points = array();
    	return $this;
    }   
    
    
    /**
     * Fetches the raw points data for this points model
     * @return array : in the format array($currency_id=>$pointsAmount, ...)
     */
    public function getPoints() 
    {
    	return $this->points;
    }

    /**
     * True if the block contains points to be displayed, otherwise false
     * @return boolean
     */
    public function hasPoints()
    {
    	return sizeof($this->points) > 0;    
    }
    
    /**
     * Alias for hasPoints();
     * @return boolean
     */
    public function isEmpty() 
    {
    	return !$this->hasPoints();
    }

    /**
     * Instantiates and returns a rendering block of this points model
     * @return TBT_Rewards_Block_Points - false if the block does not exist, the rendering block otherwise
     */
    public function getRendering()
    {
    	$block = Mage::app()->getLayout()->getBlockSingleton('rewards/points');
    	
        if ($block !== false) {
            $block->setDataModel($this);
        }
        
    	return $block;
    } 
    
    public function getSimpleAmount() 
    {
        if ($this->isEmpty()) {
            return 0;
        }
        
        return array_pop($this->points);
    }
    
    /**
     * Returns a points model that equates to the given percentage of point s
     * that the current model has.    
     * @param $percent a number where       
     */           
    public function getPercent($percent) 
    {
        $newPoints = Mage::getModel('rewards/points');
        $mulitplier = $percent /100;

        foreach ($this->getPoints() as $c => $p) {
            $p  = round($p * $mulitplier);
            $newPoints->add($c, $p);
        }
        
        return $newPoints;
    }

    public function __toString() 
    {
        $str = $this->getRendering()->setDataFromModel($this->getData())->setDataModel($this)->__toString();
    	return $str;
    }
}

