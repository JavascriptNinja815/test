<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Sales Quote address model
 *
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Matt_Nopobox_Model_Quote_Address extends Mage_Sales_Model_Quote_Address{
	/**
	 * Validate address attribute values
	 *
	 * @return bool
	 */
	public function validate(){
		$active = Mage::getStoreConfig('nopobox/option/active');
                if($active && $this->getData('address_type') != 'billing'){
			$errors = array();
			$allowcountry = Mage::getStoreConfig('nopobox/option/allowspecific');
			$flag = true;
			if($allowcountry){
				$specificcountry = Mage::getStoreConfig('nopobox/option/specificcountry');
				$specificcountry = explode(',', $specificcountry);
				if(! in_array($this->getCountryId(), $specificcountry)){
					$flag = false ;
					$_error = Mage::helper('core')->__(Mage::getStoreConfig('nopobox/option/cerror'));
				}
			}else{
				$flag = false;
			}
			//add filter for P.O. Box
			if(!$flag){
				$re = "/p\.* *o\.* *box/i";
				if(preg_match($re , $this->getStreet(1)) || preg_match($re, $this->getStreet(2)) ){
					if(isset($_error)){
						$errors[] = $_error;
					}else{
						$errors[] = Mage::helper('core')->__(Mage::getStoreConfig('nopobox/option/aerror'));
					}
				}
			}
			$_errors = parent::validate();
			if( ( empty($errors) && $_errors === true ) || $this->getShouldIgnoreValidation()){
				return true;
			}
			if($_errors && is_array($_errors)){
				$errors = array_merge($_errors , $errors);
			}
			return $errors;
		}else{
			return parent::validate();
		}
	}
}
