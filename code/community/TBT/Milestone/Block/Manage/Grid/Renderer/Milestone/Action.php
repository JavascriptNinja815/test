<?php

class TBT_Milestone_Block_Manage_Grid_Renderer_Milestone_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected $_customerGroups = null;

    public function render(Varien_Object $row)
    {
        $milestoneDetails = $this->_getValue($row);
        
        $milestoneDetailsDecoded = json_decode($milestoneDetails, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            $milestoneDetails = $milestoneDetailsDecoded;
        }

        // if $milestoneDetails doesn't exist, it's an older version
        if (is_null($milestoneDetails)) {
            return 'Data Not Available.';
        }

        if ($row->getActionType() == TBT_Rewards_Model_Special_Action::ACTION_TYPE_CUSTOMER_GROUP) {
            $element = $this->_getCustomerGroupName($milestoneDetails['action']['from']) . '  =>  '
                . $this->_getCustomerGroupName($milestoneDetails['action']['to']);

            return $element;
        }

        if ($row->getActionType() == TBT_Rewards_Model_Special_Action::ACTION_TYPE_GRANT_POINTS) {
            $element = Mage::getModel('rewards/points')->setPoints(1, $milestoneDetails['action']['points']);
        }

        return $element;
    }

    protected function _getCustomerGroupName($groupId)
    {
        if (!isset($this->_customerGroups[$groupId])) {
            $customerGroup = Mage::getModel('customer/group')->load($groupId);
            $this->_customerGroups[$groupId] = ($customerGroup->getId()) ? $customerGroup->getCode()
                : 'Customer Group Removed (ID: ' . $groupId . ')' ;

        }

        return $this->_customerGroups[$groupId];
    }
}
