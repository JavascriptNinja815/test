<?php

class TBT_Rewards_Helper_Platform_Remote extends Mage_Core_Helper_Abstract
{

    const REMOTE_PATH_DASHBOARD = '/rewardsplatform/remote/dashboard';
    const REMOTE_PATH_DASHBOARD_WIDGET = '/rewardsplatform/remote/dashboardWidget';


    /**
     * @return bool|string remote url to dashboard widget contents. False if no platform login.
     */
    public function getDashboardWidgetUrl()
    {
        return $this->getRemoteUrl(self::REMOTE_PATH_DASHBOARD_WIDGET, true);
    }

    /**
     * @return bool|string remote url to dashboard contents. False if no platform login.
     */
    public function getDashboardUrl()
    {
        return $this->getRemoteUrl(self::REMOTE_PATH_DASHBOARD, true);
    }

    /**
     * Will generate an absolute URL to the platform based on relative path provided.
     * Will optionally include optional authentication token, GET parameters.
     *
     * @param string $remotePath, relative path to platform resource
     * @param bool $includeAuthParams (optional, default true)
     * @return bool|string. Will return boolean false, if $includeAuthParams is true, but no authentication available
     *
     */
    public function getRemoteUrl($remotePath, $includeAuthParams = true)
    {
        $apiUrl = Mage::getStoreConfig('rewards/developer/apiurl');
        $params = array();

        if ($includeAuthParams) {
            $apiKey = Mage::getStoreConfig('rewards/platform/apikey');
            $encryptedApiSecret = Mage::getStoreConfig('rewards/platform/secretkey');

            if (empty($apiKey) || empty($encryptedApiSecret)) {
                return false;
            }

            $apiSecret = Mage::helper('core')->decrypt($encryptedApiSecret);
            $hash = md5($apiKey . $apiSecret);
            $params = array(
                'key'   => $apiKey,
                'hash'  => $hash
            );
        }

        $scheme = Mage::app()->getRequest()->getScheme();
        $remoteUrl = Mage::helper('core/url')->addRequestParam("{$scheme}://{$apiUrl}{$remotePath}", $params);
        return $remoteUrl;
    }
}