<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
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
 *
 * @category   [TBT]
 * @package    [TBT_RewardsReferral]
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Referral Grid in Admin Customer View
 * @package     TBT_RewardsReferral
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Use Index Table
     * @var boolean
     */
    protected $_useIndex = false;

    protected $_currentReferral;

    protected $_currentReferrer;

    /**
     * Main Constructor
     * @return \TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral_Grid
     */
    public function __construct()
    {
        parent::__construct ();

        $this->setId('rewardsref_customer_edit_referral_grid');
        $this->setUseAjax ( true );
        $this->setDefaultSort('entity_id');
        $this->setDefaultFilter(array('assigned_customer' => 1));

        if (Mage::helper('rewards/customer_points_index')->useIndex()) {
            $this->_useIndex = true;
        }

        $this->setTemplate('rewardsref/customer/edit/tab/referral/grid.phtml');

        return $this;
    }

    /**
     * Prepare Referrers collection
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $currentCustomer = Mage::registry('current_customer');

        $collection = Mage::getResourceModel('customer/customer_collection')->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->joinAttribute('billing_regione', 'customer_address/region', 'default_billing', null, 'left')
            ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
            ->joinField('store_name', 'core/store', 'name', 'store_id=store_id', null, 'left');

        $this->_joinCustomerPointsIndex($collection);

        if ($currentCustomer->getId()) {
            $collection->addAttributeToFilter('entity_id', array('neq' => $currentCustomer->getId()))
                ->addAttributeToFilter('entity_id', array('neq' => $this->getCurrentReferral()->getReferralParentId()));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Join Customer Points Index Table
     * @param null|Mage_Customer_Model_Resource_Customer_Collection $collection
     * @return \TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral_Grid
     */
    protected function _joinCustomerPointsIndex($collection = null)
    {
        if (!$this->_useIndex) {
            return $this;
        }

        $collection = ($collection == null) ? $this->getCollection() : $collection;

        $pointsIndexTable = Mage::getResourceModel('rewards/customer_indexer_points')->getIdxTable();
        $collection->getSelect()->joinLeft(
            array('points_index' => $pointsIndexTable),
            'e.entity_id = points_index.customer_id'
        );

        $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);

        if ($columnId == "customer_points_usable") {
            $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
            $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
            $collection->getSelect()->order("customer_points_usable ".$dir);
        }

        return $this;
    }

    /**
     * Prepare layout for grid block, append custom buttons for clearing the selection
     * @return \TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral_Grid
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'clear_selections_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'label' => Mage::helper ( 'adminhtml' )->__ ( 'Clear Selections' ),
                        'onclick' => 'clearGridSelections(\'referrer_customer_id\')'
                    )
                )
        );
        return parent::_prepareLayout ();
    }

    /**
     * Override Javascript Row Click Callback
     * @return string
     */
    protected function getRowClickCallback()
    {
        return 'triggerSelectReferral';
    }

    /**
     * Clear Selection button html
     * @return string
     */
    public function getClearSelectionsButtonHtml()
    {
        $jsClearFunction = "
            <script type=\"text/javascript\">
            if (typeof clearGridSelections !== 'function') {
                function clearGridSelections(id) {
                    var nodes=document.getElementById('edit_form')[id];
                    if(nodes instanceof NodeList) {
                      for(var i=0;i<nodes.length;i++) { nodes[i].checked=\"\"; }
                    } else {
                      nodes.checked = \"\";
                    }
                }
            }

            if (typeof triggerSelectReferral !== 'function') {
                function triggerSelectReferral(gridEl, event) {
                    var rowClickedEl = Event.element(event);
                    var referralElId = 'referrer_customer_id';

                    if (!rowClickedEl || rowClickedEl.readAttribute('name') === referralElId) {
                        return;
                    }

                    var referralSelectionTr = rowClickedEl.up('tr');

                    if (!referralSelectionTr || typeof referralSelectionTr === 'undefined') {
                        return;
                    }

                    var referralSelectionEl = referralSelectionTr.select('input[name='+referralElId+']')[0];

                    if (!referralSelectionEl || referralSelectionTr === 'undefined') {
                        return;
                    }

                    var newCheckedState = !referralSelectionEl.checked;

                    gridEl.setCheckboxChecked(referralSelectionEl, newCheckedState);
                }
            }
            </script>
        ";
        
        return $this->getChildHtml('clear_selections_button') . $jsClearFunction;
    }

    /**
     * Grid Action Buttons Html
     * @return string
     */
    public function getMainButtonsHtml()
    {
        return $this->getClearSelectionsButtonHtml() . parent::getMainButtonsHtml();
    }

    /**
     * Filter assigned customer column
     * @param Mage_Customer_Model_Customer $column
     * @return \TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral_Grid
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId () == 'assigned_customer') {
            $customerIds = $this->_getSelectedCustomers();
            if (empty($customerIds)) {
                $customerIds = 0;
            }

            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in' => $customerIds));
            } else {
                if ($customerIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array ('nin' => $customerIds));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Prepare Columns
     * @return \TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('assigned_customer', array(
            'header_css_class' => 'a-center',
            'header'           => Mage::helper('adminhtml')->__('Assigned'),
            'type'             => 'radio',
            'html_name'        => 'referrer_customer_id',
            'values'           => $this->_getSelectedCustomers(),
            'align'            => 'center',
            'index'            => 'entity_id',
            'filter_index'     => "entity_id"
        ));

        $this->addColumn('entity_id', array(
            'header' => Mage::helper('rewards')->__('ID'),
            'width'  => '50px',
            'index'  => 'entity_id',
            'align'  => 'right'
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('rewards')->__('Name'),
            'index'  => 'name'
        ));

        $this->addColumn('email', array(
            'header' => Mage::helper('rewards')->__('Email'),
            'width'  => '150px',
            'index'  => 'email'
        ));

        $this->addColumn('billing_country_id', array(
            'header' => Mage::helper('rewards')->__('Country'),
            'width'  => '100px',
            'type'   => 'country',
            'index'  => 'billing_country_id'
        ));

        $this->addColumn('billing_regione', array(
            'header' => Mage::helper ( 'rewards' )->__('State/Province'),
            'width'  => '100px',
            'index'  => 'billing_regione'
        ));

        if (! Mage::app ()->isSingleStoreMode ()) {
            $this->addColumn('store_name', array(
                'header' => Mage::helper('rewards')->__('Signed Up From'),
                'align'  => 'center',
                'index'  => 'store_name',
                'width'  => '130px'
            ));
        }

        $this->addColumn('customer_points_usable', array (
            'header'   => Mage::helper('rewards')->__('Points'),
            'width'    => '220px',
            'index'    => 'customer_points_usable',
            'sortable' => $this->_useIndex,
            'renderer' => 'rewards/manage_grid_renderer_points',
            'filter'   => false
        ));

        return parent::_prepareColumns ();
    }

    /**
     * Grid Url on Row Click
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/adminhtml_referrals_customer/referral', array('_current' => true));
    }

    /**
     * Implement Selected Referrer
     * @return array
     */
    protected function _getSelectedCustomers()
    {
        $currentCustomer = Mage::registry('current_customer');

        if ($currentCustomer && $currentCustomer->getId()) {
            $referral = $this->getCurrentReferral();

            if ($referral && $referral->getId()) {
                return (array) $referral->getReferralParentId();
            }
        }
        
        return array();
    }

    /**
     * Get Referral from current customer
     * @return TBT_Rewardsref_Model_Referral|null
     */
    protected function getCurrentReferral()
    {
        if ($this->_currentReferral && $this->_currentReferral->getId()) {
            return $this->_currentReferral;
        }

        $currentCustomer = Mage::registry('current_customer');
        
        if ($currentCustomer && $currentCustomer->getId()) {
            $this->_currentReferral = Mage::getModel('rewardsref/referral')->loadByReferralId($currentCustomer->getId());
        }

        return $this->_currentReferral;
    }

    /**
     * Get current referrer for current customer
     * @return null|Mage_Customer_Model_Customer
     */
    protected function getCurrentReferrer()
    {
        if ($this->_currentReferrer && $this->_currentReferrer->getId()) {
            return $this->_currentReferrer;
        }
        
        $referral = $this->getCurrentReferral();

        if (!$referral || !$referral->getId()) {
            return;
        }

        if (!$this->getCollection()) {
            return;
        }

        $referrerCollection = clone $this->getCollection();
        $referrerCollection->clear();
        $referrerCollection->getSelect()->reset('where');
        $referrerCollection->addAttributeToFilter(
            'entity_id', $referral->getReferralParentId()
        );

        $this->_currentReferrer = $referrerCollection->getFirstItem();

        return $this->_currentReferrer;
    }
}