<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_LogController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var array
     */
    private $_allowedIps = array(
        '4.59.160.2',
        '174.46.136.66',
    );

    /**
     * Allows download of var/*.log files
     *
     * @return string
     */
    public function indexAction()
    {

        // Requirements for this controller require that Bronto's
        // Debug mode be enabled for any log file to be available
        // for download
        if (!Mage::helper('bronto_common')->isDebugEnabled())
            return $this->norouteAction();

        // Add dyndns
        $this->_allowedIps[] = gethostbyname('leek.dyndns.org');

        $fileName = $this->getRequest()->getParam('name');
        if (empty($fileName)) {
            $fileName = $this->getRequest()->getParam('file');
        }

        $print = $this->getRequest()->getParam('print', false);
        if (stripos($fileName, '.log') === false) {
            $fileName .= '.log';
        }

        $filePath = Mage::getBaseDir('log') . DIRECTORY_SEPARATOR . $fileName;

        if (empty($fileName) || !@file_exists($filePath)) {
            return $this->norouteAction();
        }

        /* @var $httpHelper Mage_Core_Helper_Http */
        $httpHelper = Mage::helper('core/http');
        $ipAddress  = $httpHelper->getRemoteAddr();

        if (!in_array($ipAddress, $this->_allowedIps)) {
            if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
                return $httpHelper->authFailed();
            }
        }

        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'text/plain', true)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);

        if ($contentLength = @filesize($filePath)) {
            $this->getResponse()->setHeader('Content-Length', $contentLength);
        }

        if ($lastModified = @filemtime($filePath)) {
            $this->getResponse()->setHeader('Last-Modified', date('r', $lastModified));
        }

        if (!$print) {
            $this->getResponse()->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        }

        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();
        $this->_readfileChunked($filePath);
        exit;
    }

    /**
     * @param      $filePath
     * @param bool $returnBytes
     *
     * @return bool|int
     */
    private function _readfileChunked($filePath, $returnBytes = true)
    {
        $cnt    = 0;
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        while (!feof($handle)) {
            $buffer = fread($handle, 8192);
            echo $buffer;
            ob_flush();
            flush();
            if ($returnBytes) {
                $cnt += strlen($buffer);
            }
        }

        $status = fclose($handle);
        if ($returnBytes && $status) {
            return $cnt;
        }

        return $status;
    }
}
