<?php

class TBT_Testsweet_Model_Test_Suite_Rewards_Birthday_Points extends TBT_Testsweet_Model_Test_Suite_Abstract
{
    public function getRequireTestsweetVersion() 
    {
        return '1.0.0.0';
    }

    public function getSubject()
    {
        return $this->__('Check birthday points');
    }

    public function getDescription()
    {
        return $this->__('Check if any customers have not received birthday points from any of the active rules.');
    }

    protected function generateSummary()
    {
        $rules = Mage::getModel('rewards/special')->getCollection()
            ->addFieldToFilter('is_active', '1');

        $birthdayRules = array();
        $actionType = TBT_Rewards_Model_Birthday_Action::ACTION_CODE;
        foreach ($rules as $rule) {
            $ruleConditions = Mage::helper('rewards')->unhashIt($rule->getConditionsSerialized());
            if (is_array($ruleConditions)) {
                if (in_array($actionType, $ruleConditions)) {
                    $birthdayRules[] = $rule;
                }
            } elseif ($ruleConditions == $actionType) {
                $birthdayRules[] = $rule;
            }
        }

        $customers = Mage::getResourceModel('customer/customer_collection')
            ->addAttributeToSelect('dob', 'inner');
        $customersMissed = array();

        $now = date("Y-m-d", strtotime(Mage::getModel('core/date')->gmtDate()));
        foreach ($customers as $customer) {
            $birthday = date("m-d", strtotime($customer->getDob()));
            foreach ($birthdayRules as $rule) {
                $customerGroupIds = explode(",", $rule->getCustomerGroupIds());
                if (array_search($customer->getGroupId(), $customerGroupIds) === false) {
                    continue;
                }
                
                if (!$rule->isApplicableToWebsite($customer->getWebsiteId())) {
                    continue;
                }

                $effectiveEnd = $rule->getToDate() ? min($rule->getToDate(), $now) : $now;
                $yearStart = date("Y", strtotime($rule->getFromDate()));
                $yearEnd = date("Y", strtotime($effectiveEnd));
                $yearDiff = $yearEnd - $yearStart;
                $signUpDate = $customer->getCreatedAt();
                
                $birthdays = 0;
                for ($i = 0; $i <= $yearDiff; $i++) {
                    $year = $yearStart + $i;
                    $start = max("{$year}-01-01", $rule->getFromDate());
                    $end = min("{$year}-12-31", $effectiveEnd);
                    $tempBd = "{$year}-{$birthday}";

                    if (
                        strtotime($tempBd) >= strtotime($start)
                        && strtotime($tempBd) <= strtotime($end)
                        && strtotime($tempBd) >= strtotime($signUpDate)
                    ) {
                        $birthdays++;
                    }
                }

                $bdTransfers = Mage::getResourceModel('rewards/transfer_collection')
                    ->addFieldToFilter('customer_id', $customer->getId())
                    ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('birthday'))
                    ->addFieldToFilter('quantity', $rule->getPointsAmount());
                $missedBirthdays = $birthdays - count($bdTransfers);
                $missedPoints = $rule->getPointsAmount() * $missedBirthdays;

                if ($missedBirthdays > 0) {
                    $customersMissed[] = $customer->getId();
                    $this->addFail("Customer #{$customer->getId()} ({$customer->getEmail()}) missed "
                        . "{$missedBirthdays} birthdays on Rule #{$rule->getId()} ({$rule->getName()}), "
                        . "totalling {$missedPoints} points.");
                }
            }
        }

        $customersMissed = array_unique($customersMissed);
        if (count($customersMissed) == 0) {
            $this->addPass("No birthdays have been missed on any of the active rules.");
        } else {
            $url = Mage::getModel('adminhtml/url')->getUrl('adminhtml/manage_special/fixBirthdays');
            $this->addNotice("A total of " . count($customersMissed) . " customers have missed their birthday points.",
                "Click <a href='{$url}'>here</a> to automatically reward all these customers now, what they " .
                "should have received on their birthday.");
        }

        return $this;
    }
}

