<?php

/**
 * This class is used to calculate social influence and pull social profiles.
 */
class TBT_Common_Model_Biglight extends Varien_Object
{
    CONST API_ENDPOINT = 'http://biglight.spearmint.io/api';

    public function getPinterestActivity($username)
    {
        $username = urlencode($username);
        $url = self::API_ENDPOINT . "/pinterest/". $username;

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_TIMEOUT, 60);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($process);

        $activity = json_decode($response, true);
        if (empty($activity)) {
            return array();
        }

        return $activity;
    }

    public function getProfilesBatch($users)
    {
        $url = self::API_ENDPOINT . '/alltheprofiles';

        $postData = json_encode(array(
            'users' => $users
        ));

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_TIMEOUT, 90);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($process, CURLOPT_POST, true);
        curl_setopt($process, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($process);

        $httpCode = curl_getinfo($process, CURLINFO_HTTP_CODE);

        if ($httpCode != '200') {
            Mage::logException(new Exception("Received irregular response from Biglight: {$httpCode}\n"
                . "Response: \"{$response}\"\n"));
            return array();
        }

        $responseData = json_decode($response, true);
        if (empty($responseData) || !isset($responseData['users'])) {
            return array();
        }

        return $responseData['users'];
    }

    /**
     * Fetches an array of profile urls for facebook, linedin, twtiter, google plus and gravatar.
     * @param  string $name  FULL name (first and last)
     * @param  string $email
     * @return array of URLS
     */
    public function getProfiles($name, $email)
    {
        $biglight = $this->_getProfileContent(array($name, $email));

        $profiles = isset($biglight['profiles']) ? $biglight['profiles'] : array();

        return $profiles;
    }

    protected function _getProfileContent($params=array())
    {
        $encodedParams = $params;
        foreach ($encodedParams as &$encodedParam) {
            $encodedParam = urlencode($encodedParam);
        }

        $urlParams = implode('/', $encodedParams);
        $url = self::API_ENDPOINT . "/getprofiles/". $urlParams;

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_TIMEOUT, 60);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($process);

        $profiles = json_decode($response, true);
        if (empty($profiles)) {
            return array();
        }

        return $profiles;
    }

    /**
     * Checks if a final result value from the Biglight response (e.g. $biglight['followers']['twitter'])
     * is actually a valid result or if it's one of the potential invalid results (unknown or inconclusive).
     *
     * @param mixed $result
     *
     * @return boolean
     */
    public function isValid($result)
    {
        return ($result != null) && ($result != 'unknown') && ($result != 'inconclusive');
    }
}
