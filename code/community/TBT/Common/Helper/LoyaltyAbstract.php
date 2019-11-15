<?php
abstract class TBT_Common_Helper_LoyaltyAbstract extends Mage_Core_Helper_Abstract {

    const PATH_BASE_CEM_URL = 'https://www.sweettoothhq.com/cem/api/';
    const PATH_TBT_PROXY    = "cem_proxy.php";
    const CONTENT_TYPE_JSON = 'Content-Type: application/json';
    const DEBUG_MODE        = false;
    const DEBUG_LOG         = false;

    const ACTION_LICENSE_VERIFY         = 'license/verify';
    const ACTION_SUBSCRIPTION_VERIFY    = 'subscription/verify';
    const ACTION_SUBSCRIPTION_GET_QUOTA = 'subscription/getQuota';

    // These keys are prefixed with the module name
    const KEY_LICENSE_KEY       = '/registration/license_key';
    const KEY_LICENSE_TOKEN     = '/registration/license_token';
    const KEY_LOYALTY_INTERVAL  = '/loyalty/interval';
    const KEY_LOYALTY_LAST      = '/loyalty/last';

    const LOYALTY_INTERVAL_DEFAULT = 86400;  // 24 hours

    protected $decodeResponses = true;

    /**
     * Module key.
     * eg. 'rewards'
     */
    abstract public function getModuleKey();

    /**
     * Module directory prefix.
     * eg. TBT_Rewards
     */
    public function getModulePrefix()
    {
        return $this->getModuleConfig('prefix');
    }

    /**
     * Readable name of module.
     * eg. 'Sweet Tooth'
     */
    public function getModuleName()
    {
        return $this->getModuleConfig('name');
    }

    /**
     * CEM Identifier.
     * eg. 'tbtrewards'
     */
    public function getModuleId()
    {
        return $this->getModuleConfig('id');
    }

    /**
     * Gets tbtcommon values for the calling module.
     * These entries must be defined in the config.xml of
     * the calling module.
     *
     * @param unknown_type $key
     * @return unknown
     */
    protected function getModuleConfig($key)
    {
        return (string)Mage::getConfig()->getNode('tbtcommon/modules/' . $this->getModuleKey() . '/' . $key);
    }

    /**
     * Returns the block key for the defined billboard type.  First checks if this particular module has specified
     * its own block for the error, otherwise resorts to the TBTCommon default billboard for this error.
     * Failing THAT, falls even further back to the TBTCommon absolute default billboard.
     *
     * @param string $billboardName A billboard type that should be fairly abstract between modules (e.g. nolicense)
     * @return string
     */
    public function getBillboard($billboardName)
    {
        $loyaltyModuleKey = Mage::helper('tbtcommon')->getLoyaltyModule($this->getModuleKey());
        $blockKey = (string)Mage::getConfig()->getNode("{$loyaltyModuleKey}/billboard/{$billboardName}/block");
        $blockKey = $blockKey ? $blockKey : (string)Mage::getConfig()->getNode("tbtcommon/billboard/{$billboardName}/block");
        return $blockKey ? $blockKey : 'tbtcommon/billboard_default';
    }

    /**
     * Tries to validate the license locally by comparing hashes
     * with the license token. If this fails, it will ping
     * our server for license validation.
     *
     * @return boolean isValid
     */
	public function isValid()
	{
	    $licenseKey = $this->getLicenseKey();
	    $licenseToken = $this->getConfigData(self::KEY_LICENSE_TOKEN);

	    if ($this->isTokenValid($licenseKey, $licenseToken)) {
	        return true;
	    }

	    // Validate license over server and save license token
	    $isValid = $this->isValidOverServer($licenseKey);

	    return $isValid;
	}

	/**
	 * Helper function to set configuration data and clear cache.
	 *
	 * @param string $keySuffix Key to be appended to the modulekey like 'rewards'
	 * @param unknown_type $value Value to be stored
	 * @return unknown
	 */
	protected function setConfigData($keySuffix, $value)
	{
	    Mage::getConfig()
            ->saveConfig($this->getModuleKey() . $keySuffix, $value)
            ->cleanCache();
        return $this;
	}

	protected function getConfigData($keySuffix) {
	    return Mage::getStoreConfig($this->getModuleKey() . $keySuffix);
	}

	/**
	 * Generates a fresh token from the license and compares it with
	 * the stored token that was created when we last validated with
	 * the server.
	 *
	 * @param unknown_type $licenseKey
	 * @param unknown_type $token
	 * @return boolean If the token validates
	 */
	protected function isTokenValid($licenseKey, $token)
	{
	    if (!$token) {
	        return false;
	    }

	    $freshToken = $this->generateLicenseToken($licenseKey);
	    return $token == $freshToken;
	}

	/**
	 * Creates a token given a license using an algorithm which
	 * will be obfuscated to the client and should be kept a secret.
	 *
	 * @param unknown_type $licenseKey
	 * @return string Resulting token
	 */
	protected function generateLicenseToken($licenseKey)
	{
	    // License key concatinated with the module key and a custom salt.
	    return md5($licenseKey . $this->getModuleKey() . Mage::getConfig()->getNode('global/crypt/key'));
	}

	/**
	 * Clears the token from the config. Which in turn, forces a license
	 * validation on the server.
	 */
	protected function clearLicenseToken()
	{
	    $this->setConfigData(self::KEY_LICENSE_TOKEN, md5('invalid'));
	    return $this;
	}

	/**
	 * Validates license on our server.
	 *
	 * @param unknown_type $licenseKey
	 * @return unknown
	 */
    protected function isValidOverServer($licenseKey)
    {
        $response = $this->fetchLicenseValidation($licenseKey);
        if (isset($response['success']) && isset($response['data'])) {
            if($response['success'] && $response['data'] == 'license_valid') {
                return true;
            }
        }
        return false;
    }

    public function getLicenseKey()
    {
   		if($this->isCemUsed()) {
            $key = $this->getCemLicense();
        } else {
            $key = Mage::getStoreConfig($this->getModuleKey() . '/registration/license_key');
        }
   		return $key;
    }

    public function isCemUsed()
    {
   		$cem_packages = Mage::getResourceModel('cem/packages');
   		if($cem_packages) {
   			if($this->packageIsInstalled()) {
   				return true;
   			}
   		}
   		return false;
    }

   	public function getCemLicense($identifier = null)
   	{
   	    if (!$identifier) {
   	        $identifier = $this->getModuleId();
   	    }

		// Read adapter
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        // Select
        $select = $read->select()
        	->from(Mage::getConfig()->getTablePrefix() . 'cem_packages')
            ->joinUsing(Mage::getConfig()->getTablePrefix() . 'cem_licenses', 'license_id')
        	->where("identifier LIKE '{$identifier}_%'")
        	->limit(1);

        // Fetch row
        $row = $read->fetchRow($select);

        $license_key =  null;
        if(isset($row['package_id']) && !empty($row['package_id'])) {
        	$license_key = $row['license_key'];
        }

        return $license_key;
	}

   	public function packageIsInstalled($identifier = null)
   	{
   	    if (!$identifier) {
   	        $identifier = $this->getModuleId();
   	    }

        // Read adapter
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        // Select
        $select = $read->select()
        	->from(Mage::getConfig()->getTablePrefix() . 'cem_packages')
        	->where("identifier LIKE '%{$identifier}%'")
        	->limit(1);

        // Fetch row
        $row = $read->fetchRow($select);

        if(isset($row['package_id']) && !empty($row['package_id'])) {
        	return true;
        }

        return false;
	}

	public function getCemUrl()
	{
        if ($this->isDebugMode()) {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        } else {
            return self::PATH_BASE_CEM_URL;
        }
    }

    public function fetchResponse($action, $data)
    {
        $json = $this->fetchResponseJson($action, $data);

        if (!$this->decodeResponses) {
            return $json;
        }

        $response = json_decode($json, true);

        // Handle todo actions if present
        if (isset($response['todo'])) {
            $this->handleTodoResponse($response['todo']);
        }

        return $response;
    }

    public function fetchResponseJson($action, $data)
    {
        $path = self::PATH_TBT_PROXY;
        $identifier = $this->getModuleId();
        $license = $this->getLicenseKey();
        $base_url = Mage::getBaseUrl();

        $key = array();

        $key['identifier'] = $identifier;
        $key['license'] = $license;
        $key['base_url'] = $base_url;

        if (false /* TODO: fetch anonymous flag per action */) {
            $key['anonymous_id'] = 'id1'; // TODO: fetch anonymous id
        }

        $message = array(
            "key" => $key,
            "action" => $action,
            "data" => $data
        );

        $json = json_encode($message);

		$url =  $this->getCemUrl() . $path;

		if (self::DEBUG_LOG) {
		    Mage::log('Request: ' . $json, null, $this->getModuleKey() . '.log');
		}

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(self::CONTENT_TYPE_JSON));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            if (!$result) {
                throw new Exception('Communication Error');
            }

        } catch (Exception $e) {

            // Return result in the same format as a server response
            $errorResult = array(
                "success" => false,
                "message" => $e->getMessage(),
                "data" => null,
                "errors" => array()
            );
            $result = json_encode($errorResult);
        }

        if (self::DEBUG_LOG) {
            Mage::log('Response: ' . $result, null, $this->getModuleKey() . '.log');
        }

        return $result;
    }

    /**
     * Process any instructions from our server.
     *
     * @param unknown_type $todo
     * @return unknown
     */
    protected function handleTodoResponse($todo)
    {
        if (!is_array($todo)) {
            return $this;
        }

        if (isset($todo['callback_interval'])) {
            $this->handleTodoInterval($todo['callback_interval']);
        }

        if (isset($todo['validate_license'])) {
            $this->handleTodoValidateLicense($todo['validate_license']);
        }

        return $this;
    }

    /**
     * This is where we can set the time interval between
     * periodic communications with our server.
     *
     * @param unknown_type $newInterval
     * @return unknown
     */
    protected function handleTodoInterval($newInterval)
    {
        $interval = $this->getPingInterval();

        if (!is_numeric($newInterval)) {
            return $this;
        }

        // Ignore if no change
        if ($interval == $newInterval) {
            return $this;
        }

        $interval = $newInterval;
        $this->setConfigData(self::KEY_LOYALTY_INTERVAL, $interval);
        return $this;
    }

    /**
     * By clearing the license token, the next interaction
     * with our module will validate the license in our
     * server.
     *
     * @param unknown_type $doValidation
     */
    protected function handleValidateLicense($doValidation)
    {
        if ($doValidation) {
            $this->clearLicenseToken();
        }
    }

    protected function getPingInterval()
    {
        $interval = $this->getConfigData(self::KEY_LOYALTY_INTERVAL);

        if (!$interval) {
            $interval = self::LOYALTY_INTERVAL_DEFAULT;
        }
        return $interval;
    }

    protected function getLastPing()
    {
        $last = $this->getConfigData(self::KEY_LOYALTY_LAST);

        if (!$last) {
            $last = 0;
        }
        return $last;
    }

    /**
     * This function should be called by the module to inform
     * that the module is in use. A good way to do this is to
     * add the call in the preDispatch function of all controllers
     * by having them all extend a common controller with this call
     * implemented.
     *
     * This function will trigger any events that need to
     * happen on a periodic basis (if their interval is elapsed).
     * Huge benefit to this is no cron jobs and events do not happen
     * if the module is not in use.
     *
     * @return unknown
     */
    public function onModuleActivity()
    {
        $time = time();
        $last = $this->getLastPing();
        $interval = $this->getPingInterval();

        // Reset on clock change
        $reset = $time < $last;

        // Trigger loyalty checker if first time or interval has elapsed
        if (!$last || $last + $interval < $time || $reset || $this->_shouldSkipInterval()) {

            // Do scheduled actions
            $this->recurringActionsHook();

            // Update last callback time
            $this->setConfigData(self::KEY_LOYALTY_LAST, $time);
        }

        return $this;
    }

    /**
     * We might want to skip the delay interval, for example if recurringActionsHook disabled STR, we shouldn't wait
     * for the interval to pass before checking again if STR should be re-enabled.
     * Overwrite in child class if needed.
     *
     * @return bool True, if interval should be skipped and run recurring actions right now, false otherwise.
     */
    protected function _shouldSkipInterval()
    {
        return false;
    }

    /**
     * This function should be implemented by a child class
     * in order to run periodic actions defined by the server.
     * Eg. sending metrics, validating license, etc
     *
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    protected function recurringActionsHook()
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions every time an admin controller
     * is dispatched for the specified module.
     * @param TBT_Common_Admin_AbstractController $action The controller being dispatched
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onAdminPreDispatch($action)
    {
        return $this;
    }

    public function onAdminPostDispatch($action)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions before a model of the specified
     * module is loaded.
     * @param TBT_Common_Model_Abstract $model
     * @param integer $id
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelBeforeLoad($model, $id, $field = null)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions after a model of the specified
     * module is loaded.
     * @param TBT_Common_Model_Abstract $model
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelAfterLoad($model)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions before a model of the specified
     * module is saved.
     * @param TBT_Common_Model_Abstract $model
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelBeforeSave($model)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions after a model of the specified
     * module is saved.
     * @param TBT_Common_Model_Abstract $model
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelAfterSave($model)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions after a transaction commit
     * completes for the model of the specified module.
     * @param TBT_Common_Model_Abstract $model
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelAfterCommitCallback($model)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions before a model of the specified
     * module is deleted.
     * @param TBT_Common_Model_Abstract $model
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelBeforeDelete($model)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions after a model of the specified
     * module is deleted.
     * @param TBT_Common_Model_Abstract $model
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onModelAfterDelete($model)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions before a block of the specified
     * module gets rendered.
     * @param TBT_Common_Block_Admin_Abstract $block
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onBlockBeforeToHtml($block)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions after a block of the specified
     * module has been rendered.
     * @param TBT_Common_Block_Admin_Abstract $block
     * @param string $html The HTML string that was generated, persists down the stack
     */
    public function onBlockAfterToHtml($block, &$html)
    {
        return $this;
    }

    /**
     * This function should be implemented by a child class
     * in order to perform actions before a block of the specified
     * module renders one of its children.
     * @param TBT_Common_Block_Admin_Abstract $block
     * @param string $name
     * @param string $child
     * @return TBT_Common_Helper_LoyaltyAbstract
     */
    public function onBlockBeforeChildToHtml($block, $name, $child)
    {
        return $this;
    }

    public function fetchLicenseValidation($license = null)
    {
        if ($license === null) {
            $license = $this->getLicenseKey();
        }

        $data = array(
            'license_key' => $license
        );

        $response = $this->fetchResponse(self::ACTION_LICENSE_VERIFY, $data);

        if (isset($response['success']) && isset($response['data'])) {
            if($response['success'] && $response['data'] == 'license_valid') {

                // Generate and save token for local validation
                $token = $this->generateLicenseToken($license);
                $this->setConfigData(self::KEY_LICENSE_TOKEN, $token);

                return $response;
            }
        }

        // Clear token if authentication fails
        $this->clearLicenseToken();
        return $response;
    }

    /**
     * Allows a caller to receive fetch calls in json
     * instead of the default arrray structure.
     *
     * @param bool $decodeBoolean
     */
    public function setDecodeResponses($decodeBoolean)
    {
        $this->decodeResponses = $decodeBoolean;
        return $this;
    }

    protected function isDebugMode()
    {
        return self::DEBUG_MODE;
    }
}
