<?php

class Bronto_Common_Model_System_Config_Source_Coupon
{
    private $_options;

    /**
     * Gathers all of the sales rules on the system
     *
     * @return array
     */
    protected function _fillOptions($default = false)
    {
        $options = array();
        /** @var Mage_SalesRule_Model_Resource_Rule_Collection $rules */
        $rules = Mage::getModel('salesrule/rule')->getCollection();

        // If there are any rules
        if ($rules->count()) {
            // Cycle Through Rules
            foreach ($rules as $rule) {
                // If rule is not active, the from date or to date are invalid, or rule doesn't have a coupon just skip this rule
                if (
                    !$rule->getIsActive() ||
                    (!is_null($rule->getFromDate()) && $rule->getFromDate() > Mage::getModel('core/date')->date('Y-m-d')) ||
                    (!is_null($rule->getToDate()) && $rule->getToDate() < Mage::getModel('core/date')->date('Y-m-d')) ||
                    ($rule->getCouponType() == Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON) ||
                    ($rule->getCouponType() == Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC && $rule->getUseAutoGeneration())
                ) {
                    continue;
                }

                // Handle Coupon Label
                $couponLabel = '(Coupon: *Auto Generated*)';
                if ($couponCode = $rule->getPrimaryCoupon()->getCode()) {
                    $couponLabel = "(Coupon: {$couponCode})";
                }

                // Build Option
                $options[] = array(
                    'label' => "{$rule->getName()} {$couponLabel}",
                    'value' => $rule->getRuleId(),
                );
            }
        }

        $noneSelected = '-- None Selected --';
        if ($default) {
            $noneSelected = '-- Use Default --';
        }

        // Add -- None Selected -- Option
        array_unshift($options, array(
            'label' => Mage::helper('bronto_common')->__($noneSelected),
            'value' => ''
        ));

        return $options;
    }

    /**
     * Retrieve option array of sales rules
     *
     * @return array
     */
    public function toOptionArray($noneSelected = false)
    {
        if (empty($this->_options)) {
            $this->_options = $this->_fillOptions($noneSelected);
        }
        return $this->_options;
    }
}
