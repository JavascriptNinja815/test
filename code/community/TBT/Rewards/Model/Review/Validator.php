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
 * Special Validator
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Review_Validator extends TBT_Rewards_Model_Special_Validator {
	
	/**
	 * Returns all rules that apply to a given review
	 *
	 * @return array(TBT_rewards_Model_Special)
	 */
	public function getApplicableRulesOnReview() {
		return $this->getApplicableRules ( TBT_Rewards_Model_Special_Action::ACTION_WRITE_REVIEW, TBT_Rewards_Model_Special_Action::ACTION_RATING );
	}
	
	/**
	 * Returns an array outlining the number of points they will receive for making a review
	 *
	 * @return array
	 */
	public function getPredictPoints() {
		Varien_Profiler::start ( "TBT_Rewards:: Predict Review Points" );
		$ruleCollection = $this->getApplicableRulesOnReview ();
		$predict_array = array ();
		foreach ( $ruleCollection as $rule ) {
			$predict_array [$rule->getPointsCurrencyId ()] = $rule->getPointsAmount ();
		}
		
		Varien_Profiler::stop ( "TBT_Rewards:: Predict Review Points" );
		return $predict_array;
	}

}