<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kit Lee
 */
class Collinsharper_Toolsxss_Model_Observer
{
    function cleanXSS() {

		if(class_exists('Mage') && Mage::app()->getStore()->isAdmin()) {
            return $this;
        }

        // Start the filtering
        try {
            // Preparing the Filter object
            $_objZendFilter = new Zend_Filter();

            $_objZendHTMLFilter = new Zend_Filter_HtmlEntities();
            $_objZendHTMLFilter->setQuoteStyle(ENT_NOQUOTES);
            $_objZendHTMLFilter->setCharSet("UTF-8");

            // Setting up the filter object
            $_objZendFilter->addFilter($_objZendHTMLFilter);

            // We will filter the POST and GET or REQUEST,
            // We will leave Server and Cookies alone as it will need the content untouch

            // POST::
            foreach ($_POST as $key => $val) {

                if (is_array($val)) {

                    // We had an array, work on an array instead
                    $_arrayKey = null;
                    foreach ($_POST[$key] as $_key) {

						if(strstr($key,'password')) {
							continue;
						}
                        $_arrayKey[] = $_objZendFilter->filter($_key);
                    }

                    // Put the data back
                    $_POST[$key] = $_arrayKey;

                } else {

                    $_POST[$key] = $_objZendFilter->filter($_POST[$key]);

                }
            }

            // REQUEST::
            foreach ($_REQUEST as $key => $val) {

                $_REQUEST[$key] = $_objZendFilter->filter($_REQUEST[$key]);

            }

            // GET::

            foreach ($_GET as $key => $val) {

                $_GET[$key] = $_objZendFilter->filter($_GET[$key]);
            }

            // COOKIE::
            // We have to reinitialize the filter object and remove the quote and no encoding
            unset($_objZendFilter);
            unset($_objZendHTMLFilter);

            $_objZendFilter = new Zend_Filter();

            $_objZendHTMLFilter = new Zend_Filter_HtmlEntities();

            // Setting up the filter object
            $_objZendFilter->addFilter($_objZendHTMLFilter);

            foreach ($_COOKIE as $key => $value) {

                $_tempCookieValue = $_objZendFilter->filter($value);
                $_COOKIE[$key] = $_tempCookieValue;
                unset($_tempCookieValue);

            }
        }
        catch (Exception $error) {
            if(class_exists('Mage')) {
                Mage::Log(__FILE__ ." " . __LINE__ . " " . $error->getMessage());
            }
        }



    }

}
