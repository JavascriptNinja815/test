<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Model_Observer extends Mage_Core_Model_Abstract
{
    private $_fieldMap = array();

    /**
     * Observes module becoming enabled and displays message warning user to configure settings
     *
     * @param Varien_Event_Observer $observer
     */
    public function watchEnableAction(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('bronto_customer')->__(Mage::helper('bronto_customer')->getModuleEnabledText()));
    }

    /**
     * @param  mixed $storeId
     * @param  int   $limit
     *
     * @return array
     */
    public function processCustomersForStore($storeId = null, $limit)
    {
        if (!$limit) {
            Mage::helper('bronto_customer')->writeDebug('  Limit empty. Skipping...');

            return false;
        }

        /** @var Mage_Core_Model_Store $store */
        $store   = Mage::app()->getStore($storeId);
        $storeId = $store->getId();

        $result = array('total' => 0, 'success' => 0, 'error' => 0);
        Mage::helper('bronto_customer')->writeDebug("Starting Customer Import process for store: {$store->getName()} ({$storeId})");

        if (!$store->getConfig(Bronto_Customer_Helper_Data::XML_PATH_ENABLED)) {
            Mage::helper('bronto_customer')->writeDebug('  Module disabled for this store. Skipping...');

            return false;
        }

        // Retrieve Store's configured API Token
        $token = $store->getConfig(Bronto_Common_Helper_Data::XML_PATH_API_TOKEN);

        /** @var Bronto_Common_Model_Api $api */
        $api = Mage::helper('bronto_customer')->getApi($token);

        /** @var Bronto_Api_Contact $contactObject */
        $contactObject = $api->getContactObject();

        // Get all customers in queue who haven't been imported into bronto
        $customerRows = Mage::getModel('bronto_customer/queue')
            ->getCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->orderByUpdatedAt()
            ->setPageSize($limit)
            ->addStoreFilter($storeId)
            ->getItems();

        if (empty($customerRows)) {
            Mage::helper('bronto_customer')->writeVerboseDebug('  No Customers to process. Skipping...');

            return $result;
        }

        /** @var Mage_Customer_Model_Entity_Attribute_Collection $customerAttributes */
        $customerAttributes = Mage::getModel('customer/entity_attribute_collection');
        /** @var Mage_Customer_Model_Entity_Address_Attribute_Collection $addressAttributes */
        $addressAttributes = Mage::getModel('customer/entity_address_attribute_collection')->addVisibleFilter();
        $customerCache     = array();

        // For each Customer...
        foreach ($customerRows as $customerRow) {
            $customerId = $customerRow->getCustomerId();
            if ($customer = Mage::getModel('customer/customer')->load($customerId)/* @var $customer Mage_Customer_Model_Customer */) {
                Mage::helper('bronto_customer')->writeDebug("  Processing Customer ID: {$customerId} for Store ID: {$storeId}");
                $customerCache[] = array('customerId' => $customerId, 'storeId' => $storeId);

                /** @var Bronto_Api_Contact_Row $brontoContact */
                $brontoContact        = $contactObject->createRow(array());
                $brontoContact->email = $customer->getEmail();

                /* Process Customer Attributes */
                try {
                    $brontoContact = $this->_processAttributes($brontoContact, $customer, $customerAttributes, $store, 'customer');
                    $brontoContact = $this->_processRewardPoints($brontoContact, $customer, $store);
                    $brontoContact = $this->_processStoreCredit($brontoContact, $customer, $store);

                    /* Process Address Attributes */
                    $primaryAddress = $customer->getPrimaryShippingAddress();
                    if (!empty($primaryAddress)) {
                        $brontoContact = $this->_processAttributes($brontoContact, $primaryAddress, $addressAttributes, $store, 'address');
                    }

                    $brontoContact->persist();
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeError($e);
                }

                $result['total']++;
                try {
                    // Flush every 100 Customers
                    if ($result['total'] % 100 === 0) {
                        $result        = $this->_flushCustomers($contactObject, $customerCache, $result);
                        $customerCache = array();
                    }
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeError($e);

                    // Mark Customer as *not* imported
                    $customerRow->setBrontoImported(null)
                        ->save();

                    $result['error']++;
                }
            }
        }

        // Final flush (for any we miss)
        if (!empty($customerCache)) {
            $result = $this->_flushCustomers($contactObject, $customerCache, $result);
        }

        Mage::helper('bronto_customer')->writeDebug('  Success: ' . $result['success']);
        Mage::helper('bronto_customer')->writeDebug('  Error:   ' . $result['error']);
        Mage::helper('bronto_customer')->writeDebug('  Total:   ' . $result['total']);

        return $result;
    }

    /**
     * @param Bronto_Api_Contact_Row       $brontoContact
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Store        $store
     *
     * @return Bronto_Api_Contact_Row
     */
    protected function _processRewardPoints(Bronto_Api_Contact_Row $brontoContact, Mage_Customer_Model_Customer $customer, Mage_Core_Model_Store $store)
    {
        // If Reward Points is installed
        if (Mage::helper('bronto_common')->isModuleInstalled('Enterprise_Reward')) {
            $_fieldName = Mage::helper('bronto_customer')->getCustomerAttributeField('reward_points', 'store', $store->getId());

            /** @var Enterprise_Reward_Model_Reward $reward */
            $reward          = Mage::getModel('enterprise_reward/reward')->setCustomerId($customer->getId())->setWebsiteId($store->getWebsiteId())->loadByCustomer();
            $_attributeValue = $reward->getPointsBalance();

            // Skip un-mapped or empty attributes
            if (empty($_fieldName) || '_none_' == $_fieldName || !$_attributeValue || '' == $_attributeValue) {
                return $brontoContact;
            }

            // Add Formatted Currency Amount to value
            if ($reward->getCurrencyAmount() > 0) {
                $formattedValue = $reward->getFormatedCurrencyAmount();
                $_attributeValue .= " ({$formattedValue})";
            }

            // Store Bronto Key => Magento field label for errors
            if (!array_key_exists($_fieldName, $this->_fieldMap)) {
                $this->_fieldMap[$_fieldName] = 'Reward Points';
            }

            $brontoContact->setField($_fieldName, $_attributeValue);
        }

        return $brontoContact;
    }

    /**
     * @param Bronto_Api_Contact_Row       $brontoContact
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Store        $store
     *
     * @return Bronto_Api_Contact_Row
     */
    protected function _processStoreCredit(Bronto_Api_Contact_Row $brontoContact, Mage_Customer_Model_Customer $customer, Mage_Core_Model_Store $store)
    {
        // If Store Credit is installed
        if (Mage::helper('bronto_common')->isModuleInstalled('Enterprise_CustomerBalance')) {
            $_fieldName = Mage::helper('bronto_customer')->getCustomerAttributeField('store_credit', 'store', $store->getId());

            /** @var Enterprise_CustomerBalance_Model_Balance $balance */
            $balance         = Mage::getModel('enterprise_customerbalance/balance')->setCustomerId($customer->getId())->setWebsiteId($store->getWebsiteId())->loadByCustomer();
            $_attributeValue = Mage::app()->getLocale()->currency($balance->getWebsiteCurrencyCode())
                ->toCurrency($balance->getAmount());

            // Skip un-mapped or empty attributes
            if (empty($_fieldName) || '_none_' == $_fieldName || !$_attributeValue || '' == $_attributeValue) {
                return $brontoContact;
            }

            // Store Bronto Key => Magento field label for errors
            if (!array_key_exists($_fieldName, $this->_fieldMap)) {
                $this->_fieldMap[$_fieldName] = 'Store Credit';
            }

            $brontoContact->setField($_fieldName, $_attributeValue);
        }

        return $brontoContact;
    }

    /**
     * Cycle through attributes and validate against Bronto Field type
     *
     * @param Bronto_Api_Contact_Row $brontoContact
     * @param                        $source
     * @param                        $attributes
     * @param Mage_Core_Model_Store  $store
     * @param string                 $type 'customer' or 'address'
     *
     * @return Bronto_Api_Contact_Row
     */
    protected function _processAttributes(Bronto_Api_Contact_Row $brontoContact, $source, $attributes, Mage_Core_Model_Store $store, $type = 'customer')
    {
        // For each Customer attribute
        foreach ($attributes as $attribute) {
            if ('' == $attribute->getFrontendLabel()) {
                continue;
            }
            $_attributeCode = $attribute->getAttributeCode();

            // Get Attribute Field
            switch ($type) {
                case 'address':
                    $_fieldName = Mage::helper('bronto_customer')->getAddressAttributeField($_attributeCode, 'store', $store->getId());
                    break;
                default:
                    $_fieldName = Mage::helper('bronto_customer')->getCustomerAttributeField($_attributeCode, 'store', $store->getId());
                    break;
            }

            // Get Customer Attribute Value
            $_attributeValue = $this->_getReadableValue($attribute, $source->getData($_attributeCode));

            // Skip un-mapped or empty attributes
            if (empty($_fieldName) || '_none_' == $_fieldName || !$_attributeValue || '' == $_attributeValue) {
                continue;
            }

            // Store Bronto Key => Magento field label for errors
            if (!array_key_exists($_fieldName, $this->_fieldMap)) {
                $this->_fieldMap[$_fieldName] = $attribute->getFrontendLabel();
            }

            $brontoContact->setField($_fieldName, $_attributeValue);
        }

        return $brontoContact;
    }

    /**
     * Based on attribute type, pull the value or the label
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function _getReadableValue($attribute, $value)
    {
        if ('' == $value) {
            return '';
        }

        $_attributeType = $attribute->getFrontendInput();
        $_attributeCode = $attribute->getAttributeCode();
        $_attributeBack = $attribute->getBackendType();

        // Pick up Website/Store/Group Values
        switch ($_attributeCode) {
            case 'website_id':
                $websiteModel = Mage::getModel('core/website')->load($value);

                return $websiteModel->getName();
                break;
            case 'store_id':
                $storeModel = Mage::getModel('core/store')->load($value);

                return $storeModel->getName();
                break;
            case 'group_id':
                $groupModel = Mage::getModel('customer/group')->load($value);

                return $groupModel->getCode();
                break;
            case 'country_id':
                $countryModel = Mage::getModel('directory/country')->load($value);

                return $countryModel->getName();
                break;
            default:
                break;
        }

        if ($_attributeBack == 'datetime' || $_attributeType == 'date') {
            $value = $this->_formatDateValue($value);
        }

        // Format Attribute Values
        switch ($_attributeType) {
            case 'select':
            case 'boolean':
                return strtolower(Mage::helper('bronto_customer')->getAttributeAdminLabel($attribute, $value));
                break;
            case 'multiselect':
                $values = array();
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }
                if (!is_array($value)) {
                    $value = array($value);
                }

                foreach ($value as $val) {
                    $values[] = strtolower(Mage::helper('bronto_customer')->getAttributeAdminLabel($attribute, $val));
                }

                return implode(', ', $values);
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * Format the value into a Bronto acceptable date
     *
     * @param string $value
     * @return string
     */
    protected function _formatDateValue($value)
    {
        $dates = explode(' ', $value);
        $date  = $dates[0];

        if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $date)) {
            return '';
        } else {
            return implode('T', $dates) . Mage::getSingleton('core/date')->date('P');
        }
    }

    /**
     * @param  Bronto_Api_Contact $customerObject
     * @param  array              $customerCache
     * @param  array              $result
     *
     * @return array
     */
    protected function _flushCustomers($customerObject, $customerCache, $result)
    {
        $fieldModel = Mage::getModel('bronto_common/system_config_source_field');
        $flushResult = $customerObject->flush();
        $flushCount  = count($flushResult);
        Mage::helper('bronto_customer')->writeDebug("  Flush resulted in {$flushCount} customers processed");
        Mage::helper('bronto_customer')->writeVerboseDebug('===== FLUSH =====', 'bronto_customer_api.log');
        Mage::helper('bronto_customer')->writeVerboseDebug(var_export($customerObject->getApi()->getLastRequest(), true), 'bronto_customer_api.log');
        Mage::helper('bronto_customer')->writeVerboseDebug(var_export($customerObject->getApi()->getLastResponse(), true), 'bronto_customer_api.log');
        foreach ($flushResult as $i => $flushResultRow) {
            if ($flushResultRow->hasError()) {
                $hasError     = true;
                $errorCode    = $flushResultRow->getErrorCode();
                $errorMessage = $flushResultRow->getErrorMessage();
            } else {
                $hasError     = false;
                $errorCode    = false;
                $errorMessage = false;
            }

            if (isset($customerCache[$i])) {
                // Get Customer Object
                $customer     = Mage::getModel('customer/customer')->load($customerCache[$i]['customerId']);
                $store        = Mage::getModel('core/store')->load($customerCache[$i]['storeId']);
                $website      = Mage::getModel('core/website')->load($store->getWebsiteId());
                $storeMessage = "For `{$website->getName()}`:`{$store->getName()}`: ";

                $customerRow = Mage::getModel('bronto_customer/queue')
                    ->getCustomerRow($customerCache[$i]['customerId'], $customerCache[$i]['storeId']);
            } else {
                if ($hasError) {
                    Mage::helper('bronto_customer')->writeError("[{$errorCode}] {$errorMessage}");
                    $result['error']++;
                }

                continue;
            }

            if ($hasError) {
                // Catch Error and Replace Field ID with Field Name
                if (preg_match_all("/([a-zA-Z0-9\-]){36}/", $errorMessage, $matches)) { // Grab field id if exists
                    foreach ($matches[0] as $match) {
                        $fieldObject = $fieldModel->getFieldObjectById($match);
                        if ($fieldObject) {
                            $errorMessage = str_replace($match, $fieldObject->name, $errorMessage);
                        } elseif (array_key_exists($match, $this->_fieldMap)) {
                            $mageLabel    = $this->_fieldMap[$match];
                            $errorMessage = "Bronto field mapped for {$mageLabel} no longer exists in your bronto account";
                        }
                    }
                }

                // If Error Code In specified Array, suppress contact
                if (in_array($errorCode, array(302, 303, 314, 315, 317))) {
                    $customerRow->setBrontoSuppressed($errorMessage);
                }

                Mage::helper('bronto_customer')->writeError("[{$errorCode}] {$storeMessage}{$errorMessage} ({$customer->getEmail()})");

                // Mark Customer as not imported
                $customerRow->setBrontoImported(null);
                $customerRow->save();
                $result['error']++;
            } else {
                Mage::helper('bronto_customer')->writeDebug("    {$customerCache[$i]['customerId']} = SUCCESS");

                // Mark Customer as imported
                $customerRow->setBrontoImported(Mage::getSingleton('core/date')->gmtDate());
                $customerRow->save();
                $result['success']++;
            }
        }

        return $result;
    }

    /**
     * @param bool $brontoCron
     *
     * @return array
     */
    public function processCustomers($brontoCron = false)
    {
        $result = array(
            'total'   => 0,
            'success' => 0,
            'error'   => 0,
        );

        // Only allow cron to run if isset to use mage cron or is coming from bronto cron
        if (Mage::helper('bronto_customer')->canUseMageCron() || $brontoCron) {
            $limit = Mage::helper('bronto_customer')->getLimit();

            $stores = Mage::app()->getStores(true);
            foreach ($stores as $_store) {
                if ($limit <= 0) {
                    continue;
                }
                $storeResult = $this->processCustomersForStore($_store, $limit);
                $result['total'] += $storeResult['total'];
                $result['success'] += $storeResult['success'];
                $result['error'] += $storeResult['error'];
                $limit = $limit - $storeResult['total'];
            }
        }

        return $result;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function markCustomerForReimport(Varien_Event_Observer $observer)
    {
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $observer->getCustomer();

        /* @var $contactQueue Bronto_Customer_Model_Queue */
        Mage::getModel('bronto_customer/queue')
            ->getCustomerRow($customer->getId(), $customer->getStoreId())
            ->setCreatedAt($customer->getCreatedAt())
            ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
            ->setBrontoImported(null)
            ->setBrontoSuppressed(null)
            ->save();
    }

    /**
     * Grab Config Data Object before save and handle the 'Create New...' value for
     * fields that were generated dynamically
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Varien_Event_Observer
     */
    public function saveDynamicField(Varien_Event_Observer $observer)
    {
        $action = $observer->getEvent()->getControllerAction();

        if ($action->getRequest()->getParam('section') == 'bronto_customer') {
            $groups  = $action->getRequest()->getPost('groups');
            $section = $action->getRequest()->getParam('section');

            // Pre-Process Fields to Strip out and Handle Dynamic Fields
            $groups = $this->_handleDynamicAttributes($groups, $section);

            // Replace Existing 'groups' data with newly stripped 'groups' data and pass on to be saved
            $observer->getEvent()->getControllerAction()->getRequest()->setPost('groups', $groups);
        }

        return $observer;
    }

    /**
     * Process customer and address attributes and save back to observer
     *
     * @param  array $groups
     * @param string $section
     *
     * @return array
     */
    protected function _handleDynamicAttributes($groups, $section)
    {
        // Process Dynamic Customer Attribute Fields
        if (array_key_exists('attributes', $groups)) {
            $attrFieldsCustomer             = $this->_processDynamicAttributes($groups['attributes']['fields'], $section, 'attributes');
            $groups['attributes']['fields'] = $attrFieldsCustomer;
        }

        // Process Dynamic Address Attribute Fields
        if (array_key_exists('address_attributes', $groups)) {
            $attrFieldsAddress                      = $this->_processDynamicAttributes($groups['address_attributes']['fields'], $section, 'address_attributes');
            $groups['address_attributes']['fields'] = $attrFieldsAddress;
        }

        // Return Updated Groups Data
        return $groups;
    }

    /**
     * Capture "Create New..." attributes, create field in Bronto, and save field id
     *
     * @param  array $attributesFields
     * @param string $section
     * @param string $group
     *
     * @return array
     */
    protected function _processDynamicAttributes($attributesFields = array(), $section, $group)
    {
        // Create Config Object
        $config = Mage::getModel('core/config');

        // Get Admin Scope Parameters
        $scopeParams = Mage::helper('bronto_common')->getScopeParams();

        // Get Array of Attributes that are hard-coded into system.xml
        $ignore = Mage::helper('bronto_customer')->getSystemAttributes();

        // Cycle Through Attribute Fields to Find and Save Dynamic Fields
        foreach ($attributesFields as $fieldId => $field) {
            // Save Dynamic 'Create New...' Fields
            if (preg_match('/_dynamic_new/', $fieldId) || preg_match('/_new/', $fieldId)) {
                // Strip off '_dynamic_new' or '_new' from Field ID to Get real Field ID
                $realField = preg_replace('/_dynamic_new|_new$/', '', $fieldId);

                if (!is_array($field)) {
                    $value = $field;
                } else {
                    $value = $field['value'];
                }

                if (is_null($value)) {
                    continue;
                }

                try {
                    /* @var $fieldObject Bronto_Api_Field */
                    $fieldObject        = Mage::helper('bronto_common')->getApi()->getFieldObject();
                    $brontoField        = $fieldObject->createRow();
                    $brontoField->name  = $fieldObject->normalize($value);
                    $brontoField->label = $value;
                    $brontoField->type  = Bronto_Api_Field::TYPE_TEXT;

                    $brontoField->save();
                    $fieldObject->addToCache($brontoField->name, $brontoField);

                    $scope = $scopeParams['scope'];
                    if ($scope != 'default') {
                        $scope .= 's';
                    }

                    // Save Field To Config
                    $config->saveConfig(
                        $section . '/' . $group . '/' . $realField,
                        $brontoField->id,
                        $scope,
                        $scopeParams[$scopeParams['scope'] . '_id']
                    );

                    // Unset Dynamic Fields
                    unset($attributesFields[$realField]);
                    unset($attributesFields[$fieldId]);
                    unset($fieldObject);
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeError("Unable to save new field: {$value}");
                }
            } // Save Dynamic Fields
            elseif (!in_array($fieldId, $ignore[$group])) {
                $scope = $scopeParams['scope'];
                if ($scope != 'default') {
                    $scope .= 's';
                }

                // Save Field To Config
                $config->saveConfig(
                    $section . '/' . $group . '/' . $fieldId,
                    $field['value'],
                    $scope,
                    $scopeParams[$scopeParams['scope'] . '_id']
                );

                // Unset Dynamic Field
                unset($attributesFields[$fieldId]);
            }
        }

        return $attributesFields;
    }
}
