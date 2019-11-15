<?php
/**
 * @deprecated Use TBT_Rewards_Helper_Loyalty
 */
class TBT_Rewards_Helper_Loyalty_Checker extends TBT_Rewards_Helper_Loyalty {
    /**
     * Deprecated. Use TBT_Rewards_Helper_Loyalty
     */
    public function isValid()
    {
        $licenseKey = $this->getLicenseKey();
        $licenseToken = Mage::getStoreConfig(self::KEY_LICENSE_TOKEN);
        
        if ($this->isTokenValid($licenseKey, $licenseToken)) {
            return true;
        }
        
        // Validate license over server and save license token
        $isValid = $this->isValidOverServer($licenseKey);
        
        return $isValid;
    }
    
    /**
     * Validates license on our server.
     *
     * @param unknown_type $licenseKey
     * @return boolean isValid
     */
    protected function isValidOverServer($licenseKey) 
    {
        $response = $this->fetchValidationResponse($licenseKey);
        return $response == 'license_valid';
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
    protected function isTokenValid($licenseKey, $token) {
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
    protected function generateLicenseToken($licenseKey) {
        // License key concatinated with the module key and a custom salt.
        return md5($licenseKey . 'rewards' . Mage::getConfig()->getNode('global/crypt/key'));
    }
    
    protected function setConfigData($key, $value) {
        Mage::getConfig()
            ->saveConfig($key, $value)
            ->cleanCache();
        return $this;
    }
    
    /**
     * Clears the token from the config. Which in turn, forces a license
     * validation on the server.
     */
    protected function clearLicenseToken() {
        $this->setConfigData(self::KEY_LICENSE_TOKEN, md5('invalid'));
    }
    
    public function getLicenseKey() {
   		if($this->isCemUsed()) {
            $key = $this->getCemLicense('tbtrewards');
        } else {
            $key = Mage::getStoreConfig('rewards/registration/license_key');
        }
   		return $key;
    }
    
    
    public function isCemUsed() {
   		$cem_p = Mage::getResourceModel('cem/packages');
   		if($cem_p) {
   			if($this->packageIsInstalled('tbtrewards')) {
   				return true;
   			}
   		}
   		return false;
    }
    
    /**
     * Check if a package already is installed
     *
     * @param string $identifier
     * @return array
     */
   	public function getCemLicense( $identifier = null )
	{
		// Read adapter
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        // Select
        $select = $read->select()
        	->from(Mage::getConfig()->getTablePrefix() . 'cem_packages')
            ->joinUsing(Mage::getConfig()->getTablePrefix() . 'cem_licenses', 'license_id')
        	->where("identifier LIKE '{$identifier}%'")
        	->limit(1);

        // Fetch row
        $row = $read->fetchRow($select);
        
        $license_key =  null;
        if(isset($row['package_id']) && !empty($row['package_id'])) {
        	$license_key = $row['license_key'];
        }
        
        return $license_key;
	}

    /**
     * Check if a package already is installed
     *
     * @param string $identifier
     * @return array
     */
   	public function packageIsInstalled( $identifier = null )
	{	
        return $this->getCemLicense($identifier) !== null;
	}

    public function fetchValidationResponse($license) {
        $url = "http://www.wdca.ca/cem/api/verify_license.php";
        
        $fields = array(
            'license_key' => $license, 
            'identifier' => $this->getModuleId()
        );
        
        $output = $this->fetchResponse($url, $fields);
        
        // Generate license token if valid, otherwise clear it.
        if ($output == 'license_valid') {
            $this->setConfigData(self::KEY_LICENSE_TOKEN, $this->generateLicenseToken($license));
        } else {
            $this->clearLicenseToken();
        }
        
        return $output;
    }
    
    
    public function fetchUpdatesResponse() {
        $url = "http://www.wdca.ca/cem/api/check_updates.php";
        
        $fields = array(
            'license_key'   => $this->getLicenseKey(), 
            'identifier'	=> $this->getModuleId(),
            'mage_ver'	    => Mage::getVersion(),
            'module_ver'	=> (string) Mage::getConfig()->getNode('modules/TBT_Rewards/version')
        );
        
        $output = $this->fetchResponse($url, $fields);
        
        return $output;
    }

    public function fetchResponse($url, $fields) {
        //open connection
        $ch = curl_init();
        $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)';
        
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        // user agent:
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $output = curl_exec($ch);
        curl_close($ch);
	    return $output;
    	
    }
    
    public function getModuleId() {
        return 'tbtrewards';
    }
}
