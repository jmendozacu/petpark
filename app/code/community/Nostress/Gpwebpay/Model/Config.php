<?php
/**
 * Magento Module developed by NoStress Commerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@nostresscommerce.cz so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you have special needs for this module, please
 * contact us at info@nostresscommerce.cz for more information.
 *
 * @category    Nostress
 * @package     Nostress_Gpwebpay
 * @copyright   Copyright (c) 2012 NoStress Commerce (http://www.nostresscommerce.cz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Config model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Config
{
	
	const OPERATION        = "CREATE_ORDER";
	const DEFAULT_STATE    = "requested";
	const SOAP_URL_SANDBOX = "https://test.3dsecure.gpwebpay.com/webservices/services/pgw";
	const SOAP_URL         = "https://3dsecure.gpwebpay.com/webservices/services/pgw";
	
	/**
	* Current payment method code
	* @var string
	*/
	protected $_methodCode = null;
	
	/**
	* Current store id
	*
	* @var int
	*/
	protected $_storeId = null;
	
	/**
	* Set method and store id, if specified
	*
	* @param array $params
	*/
	public function __construct($params = array()) {
		if ($params) {
			$method = array_shift($params);
			$this->setMethod($method);
			if ($params) {
				$storeId = array_shift($params);
				$this->setStoreId($storeId);
			}
		}
	}
	
	/**
	* Method code setter
	*
	* @param string $method
	* @return Nostress_Gpwebpay_Model_Config
	*/
	public function setMethod($method) {
		$this->_methodCode = $method;
        return $this;
    }
	
	/**
	* Payment method instance code getter
	*
	* @return string
	*/
	public function getMethodCode() {
		return $this->_methodCode;
	}
	
	/**
	* Store ID setter
	*
	* @param int $storeId
	* @return Nostress_Gpwebpay_Model_Config
	*/
	public function setStoreId($storeId) {
		$this->_storeId = (int)$storeId;
		return $this;
	}
	
	/**
	* Check whether method active in configuration or not
	*
	* @param string $method Method code
	* @return bool
	*/
	public function isMethodActive($method) {
		if (Mage::getStoreConfigFlag("payment/{$method}/active", $this->_storeId)) {
			return true;
		}
		return false;
	}
	
	/**
	* Check whether method available for checkout or not
	*
	* @param string $method Method code
	* @return bool
	*/
	public function isMethodAvailable($methodCode = null) {
		if ($methodCode === null) {
			$methodCode = $this->getMethodCode();
		}
		
		$result = true;
		
		if (!$this->isMethodActive($methodCode)) {
			$result = false;
		}
		
		return $result;
	}
	
	/**
	* Config field magic getter
	* The specified key can be either in camelCase or under_score format
	* Tries to map specified value according to set payment method code, into the configuration value
	* Sets the values into public class parameters, to avoid redundant calls of this method
	*
	* @param string $key
	* @return string|null
	*/
	public function __get($key) {
		$underscored = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key));
		$value = Mage::getStoreConfig('payment/'.$this->getMethodCode().'/'.$underscored, $this->_storeId);
		$this->$key = $value;
		$this->$underscored = $value;
		return $value;
	}
	
	public function getOrderUrl() {
		/*$this->GPLog(0);
		$this->GPLog(1);*/
		return $this->__get('orderurl');
	}
	
	public function getGpwebpayUrl(array $params = array()) {
		return sprintf($this->getOrderUrl().'%s',
			$params ? '?'.http_build_query($params) : ''
		);
	}
	
	public function getMerchantNumber() {
		return $this->__get('merchantnumber');
	}
	
	public function getPayAction() {
		return $this->__get('payaction');
	}
	
	public function getDepositFlag() {
		if ($this->getPayAction() == "2") {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	public function getOperation() {
		return self::OPERATION;
	}
	
	public function getSandbox() {
		return $this->__get('sandbox');
	}
	
	public function getSoapUrl() {
		if ($this->getSandbox() == 0) {
			return self::SOAP_URL;
		}
		return self::SOAP_URL_SANDBOX;
	}
	
	public function getTemporaryShutdown() {
		return @unserialize($this->__get('temp_shutdown'));
	}
	
	public function getDefaultState() {
		return self::DEFAULT_STATE;
	}
}