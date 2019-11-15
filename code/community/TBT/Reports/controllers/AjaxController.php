<?php

/**
 * Class TBT_Reports_AjaxController
 * Provides a parent template class for the purpose of ajax-based reports
 */
class TBT_Reports_AjaxController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    /**
     * Overwrite preDispatch to use our custom authentication if available
     * @see Mage_Adminhtml_Controller_Action::preDispatch
     * @return $this|Mage_Adminhtml_Controller_Action
     */
    public function preDispatch()
    {
        try {
            $auth = $this->_getAuthorizationHeader();
            if (!empty($auth)) {
                $pieces = explode(' ', $auth);
                $credentials = explode(':', base64_decode($pieces[1]));
                if (count($credentials) != 2) {
                    throw new Exception("Invalid Authorization");
                }

                if ($this->_areCredentialsValid($credentials[0], $credentials[1])) {
                    // Skip normal preDispatch process
                    return $this;
                }
            }
        } catch (Exception $e) {
            // Bad syntax, call default preDispatcher
        }

        return parent::preDispatch();
    }

    /**
     * Will accept data and prepare an official JSON response
     * @param array|string|Mage_Core_Model_Abstract $data
     * @return $this
     */
    public function jsonResponse($data)
    {
        $helper = Mage::helper('rewards');
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody($helper->toJson($data));

        return $this;
    }
    
    /**
     * Plain Text Output Headers
     * @param string $html
     * @return \TBT_Reports_AjaxController
     */
    public function plainTextResponse($html)
    {
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
        $this->getResponse()->setBody($html);

        return $this;
    }

    /**
     * If there's an Authorization Header, we'll get it!
     * @return string|null
     * @throws Zend_Controller_Request_Exception
     */
    protected function _getAuthorizationHeader()
    {
        $authHeader = $this->getRequest()->getHeader('Authorization');
        if (empty($authHeader) && !empty($_SERVER['Authorization']))
            $authHeader = $_SERVER['Authorization'];

        if (empty($authHeader) && !empty($_SERVER['AUTHORIZATION']))
            $authHeader = $_SERVER['AUTHORIZATION'];

        if (empty($authHeader) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

        return (empty($authHeader) ? null : $authHeader);
    }

    /**
     * Will check ST Api Credentials against the supplied ones
     * @param string $apiKey
     * @param string $apiSecret
     * @return bool
     */
    protected function _areCredentialsValid($apiKey, $apiSecret)
    {
        $storeApiKey = Mage::getStoreConfig('rewards/platform/apikey');
        $storeApiSecret = Mage::getStoreConfig('rewards/platform/secretkey');

        if ($storeApiKey == $apiKey) {
            $decryptedStoreApiSecret = Mage::helper('core')->decrypt($storeApiSecret);
            if ($decryptedStoreApiSecret == $apiSecret) return true;

            $encryptedApiSecret = Mage::helper('core')->encrypt($apiSecret);
            if ($encryptedApiSecret == $storeApiSecret) return true;
        }

        return false;
    }

    /**
     * Will accept an array of Models or a single model,
     * and return a json object representing the object
     *
     * @see TBT_Rewards_Helper_Data::toJson()
     * @param Mage_Core_Model_Abstract|array<Mage_Core_Model_Abstract> $object
     * @return string, the final json
     */
    public function toJson($object)
    {
        return Mage::helper('rewards')->toJson($object);
    }

}
