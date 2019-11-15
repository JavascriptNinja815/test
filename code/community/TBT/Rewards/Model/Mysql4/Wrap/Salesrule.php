<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Salesrule resource wrapper class
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Mysql4_Wrap_Salesrule
    extends Mage_SalesRule_Model_Mysql4_Rule
{
    /**
     * Mass Update Salesrules Times Used
     * @param mixed $ruleIds
     * @param null|int $customerId
     * @return \TBT_Rewards_Model_Mysql4_Wrap_Salesrule
     */
    public function updateSalesrulesTimesUsed($ruleIds, $customerId = null)
    {
        if (is_array($ruleIds)) {
            $ruleIds = implode(',', $ruleIds);
        }

        $where = $this->_getWriteAdapter()->quoteInto('FIND_IN_SET(rule_id, ?) > 0', $ruleIds);
        $this->_getWriteAdapter()->update($this->getTable('salesrule/rule'), array('times_used' => new Zend_Db_Expr('`times_used` + 1')), $where);

        if (!$customerId) {
            return $this;
        }

        $this->_getReadAdapter()->disallowDdlCache();
        $indexList = $this->_getReadAdapter()->getIndexList($this->getTable('salesrule/rule_customer'));
        $this->_getReadAdapter()->allowDdlCache();

        $ruleIdsArr = explode(',', $ruleIds);
        
        if (array_key_exists('UNQ_SALESRULE_CUSTOMER_CUSTOMER_ID_RULE_ID', $indexList)) {
            $data = array();

            foreach ($ruleIdsArr as $ruleId) {
                if (!$ruleId) {
                    continue;
                }

                $data[] = array(
                    'rule_id' => $ruleId,
                    'customer_id' => $customerId,
                    'times_used' => 1
                );
            }

            if (count($data) > 0) {
                $fields = array('times_used' => new Zend_Db_Expr('`times_used` + 1'));
                $this->_getWriteAdapter()->insertOnDuplicate($this->getTable('salesrule/rule_customer'), $data, $fields);
            }
        } else {
            foreach ($ruleIdsArr as $ruleId) {
                if (!$ruleId) {
                    continue;
                }
                
                $ruleCustomer = Mage::getModel('salesrule/rule_customer');
                $ruleCustomer->loadByCustomerRule($customerId, $ruleId);

                if ($ruleCustomer->getId()) {
                    $ruleCustomer->setTimesUsed($ruleCustomer->getTimesUsed() + 1);
                } else {
                    $ruleCustomer
                        ->setCustomerId($customerId)
                        ->setRuleId($ruleId)
                        ->setTimesUsed(1);
                }
                
                $ruleCustomer->save();
            }
        }

        return $this;
    }
}