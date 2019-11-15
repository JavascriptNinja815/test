<?php

/**
 * @package   Bronto\Customer
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Helper_Data extends Bronto_Common_Helper_Data implements Bronto_Common_Helper_DataInterface
{
    const XML_PATH_ENABLED      = 'bronto_customer/settings/enabled';
    const XML_PATH_MAGE_CRON    = 'bronto_customer/settings/mage_cron';
    const XML_PATH_LIMIT        = 'bronto_customer/settings/limit';
    const XML_PATH_SYNC_LIMIT   = 'bronto_customer/settings/sync_limit';
    const XML_PATH_INSTALL_DATE = 'bronto_customer/settings/install_date';
    const XML_PATH_UPGRADE_DATE = 'bronto_customer/settings/upgrade_date';

    const XML_PREFIX_CUSTOMER_ATTR = 'bronto_customer/attributes/';
    const XML_PREFIX_ADDRESS_ATTR  = 'bronto_customer/address_attributes/';

    const XML_PATH_CRON_STRING = 'crontab/jobs/bronto_customer_import/schedule/cron_expr';
    const XML_PATH_CRON_MODEL  = 'crontab/jobs/bronto_customer_import/run/model';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Contact Import';

    /**
     * Get Human Readable Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->__($this->_name);
    }

    /**
     * Check if module is enabled
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        // Get Enabled Scope
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /*
     * Get Text to display in notice when enabling module
     *
     * @return string
     */
    public function getModuleEnabledText()
    {
        $message   = parent::getModuleEnabledText();
        $scopeData = $this->getScopeParams();
        if ($scopeData['scope'] != 'default') {
            $message = $this->__(
                'If the API token being used for this configuration scope is different from that of the Default Config scope, ' .
                'you should un-check the `Use Website` or `Use Default` for ALL <em>Customer Attributes</em> ' .
                'and <em>Address Attributes</em> on this page and select the desired fields.'
            );
        }

        return $message;
    }

    /**
     * Disable Module for Specified Scope
     *
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return bool
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        return $this->_disableModule(self::XML_PATH_ENABLED, $scope, $scopeId, $deleteConfig);
    }

    /**
     * Get Send Limit
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return int
     */
    public function getLimit($scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_LIMIT, $scope, $scopeId);
    }

    /**
     * Get Sync Limit
     *
     * @return int
     */
    public function getSyncLimit()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_SYNC_LIMIT);
    }

    /**
     * Check if module can use the magento cron
     *
     * @return bool
     */
    public function canUseMageCron()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_MAGE_CRON, 'default', 0);
    }

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return self::XML_PATH_CRON_STRING;
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return self::XML_PATH_CRON_MODEL;
    }

    /**
     * @return array
     */
    public function getSystemAttributes()
    {
        return array(
            'attributes'         => array(
                'prefix',
                'new_prefix',
                'firstname',
                'new_firstname',
                'middlename',
                'new_middlename',
                'lastname',
                'new_lastname',
                'suffix',
                'new_suffix',
                'gender',
                'new_gender',
                'dob',
                'new_dob',
                'taxvat',
                'new_taxvat',
                'website_id',
                'new_website_id',
                'group_id',
                'new_group_id',
                'created_at',
                'new_created_at',
                'created_in',
                'new_created_in',
            ),
            'address_attributes' => array(
                'street',
                'new_street',
                'city',
                'new_city',
                'region',
                'new_region',
                'postcode',
                'new_postcode',
                'country_id',
                'new_country_id',
                'company',
                'new_company',
                'telephone',
                'new_telephone',
                'fax',
                'new_fax',
            ),
        );
    }

    /**
     * Get Customer Attribute Field for scope
     *
     * @param        $attribute
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getCustomerAttributeField($attribute, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PREFIX_CUSTOMER_ATTR . $attribute, $scope, $scopeId);
    }

    /**
     * Get Address Attribute Field for scope
     *
     * @param        $attribute
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getAddressAttributeField($attribute, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PREFIX_ADDRESS_ATTR . $attribute, $scope, $scopeId);
    }

    /**
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'bronto_customer';
    }

    /**
     * Get Human Readable label for attribute value option
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param int|string                      $attributeValueId
     *
     * @return string|boolean
     */
    public function getAttributeAdminLabel($attribute, $attributeValueId)
    {
        $_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setStoreFilter(0)
            ->setAttributeFilter($attribute->getId())
            ->load();

        foreach ($_collection->toOptionArray() as $_cur_option) {
            if ($_cur_option['value'] == $attributeValueId) {
                return $_cur_option['label'];
            }

        }

        return false;
    }

    /**
     * Get Count of customers not in queue
     *
     * @return int
     */
    public function getMissingCustomersCount()
    {
        return Mage::getModel('bronto_customer/queue')
            ->getMissingCustomersCount();
    }

    /**
     * Get Customers which aren't in contact queue
     *
     * @return array
     */
    public function getMissingCustomers()
    {
        return Mage::getModel('bronto_customer/queue')
            ->getMissingCustomers();
    }

    /**
     * Does this helper have custom config for debugging
     *
     * @return boolean
     */
    public function hasCustomConfig()
    {
        return true;
    }

    /**
     * Gets the bronto customer field attributes
     *
     * @param object $store (Optional)
     *
     * @return array
     */
    public function getCustomConfig($store = null)
    {
        $customerAttributes = Mage::getModel('customer/entity_attribute_collection');
        $addressAttributes  = Mage::getModel('customer/entity_address_attribute_collection');

        $attributes = array();
        $data       = array();
        foreach ($customerAttributes as $attribute) {
            $config = $this->getCustomerAttributeField($attribute->getAttributeCode(), $store);
            if ($config && $attribute->getFrontendLabel()) {
                $data[$attribute->getAttributeCode()] = $config;
            }
        }
        $attributes['customer_attributes'] = $data;

        $data = array();
        foreach ($addressAttributes as $attribute) {
            $config = $this->getAddressAttributeField($attribute->getAttributeCode(), $store);
            if ($config && $attribute->getFrontendLabel()) {
                $data[$attribute->getAttributeCode()] = $config;
            }
        }
        $attributes['address_attributes'] = $data;

        return $attributes;
    }
}
