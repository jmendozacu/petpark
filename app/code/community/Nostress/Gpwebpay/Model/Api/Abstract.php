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
 * GPWebpay Abstract Api
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
abstract class Nostress_Gpwebpay_Model_Api_Abstract extends Varien_Object
{
	/**
	* Config instance
	* @var Nostress_Gpwebpay_Model_Config
	*/
	protected $_config = null;
	
	public function getMerchantNumber() {
		return $this->_config->getMerchantNumber();
	}
	
	public function getOperation() {
		return $this->_config->getOperation();
	}
	
	public function getDepositFlag() {
		return $this->_config->getDepositFlag();
	}
	
	public function getOrderCurrency() {
		$StoreCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();
		switch ($StoreCurrency) {
			case "CZK":
				$currency = "203";
			break;
			case "EUR":
				$currency = "978";
			break;
			case "GBP":
				$currency = "826";
			break;
			case "USD":
				$currency = "840";
			break;
			default:
				Mage::getModel('nostress_gpwebpay/abstract')->GPLog("Currency ".$StoreCurrency." not supported!");
				return $StoreCurrency;
			break;
		}
		Mage::getModel('nostress_gpwebpay/abstract')->GPLog("Currency: ".$StoreCurrency." (".$currency.")");
		
		return $currency;
	}
	
	public function getOrderAmount() {
		$orderAmount = $this->getOrder()->getGrandTotal();
		list($amount1, $amount2) = explode (".", $orderAmount);
		$amount = $amount1.substr($amount2, 0, 2);
		
		return $amount;
	}
	
	public function getDigest() {
		return Mage::getModel("nostress_gpwebpay/abstract")->sign(
			$this->getDigestData(), 1
		);
	}
	
	public function getDigestData() {
		return $this->getMerchantNumber()."|".
			$this->getOperation()."|".
			$this->getOrderId()."|".
			$this->getOrderAmount()."|".
			$this->getOrderCurrency()."|".
			$this->getDepositFlag()."|".
			$this->getReturnUrl();
	}
	
	/**
	* Config instance setter
	* @param Nostress_Gpwebpay_Model_Config $config
	* @return Nostress_Gpwebpay_Model_Api_Abstract
	*/
	public function setConfigObject(Nostress_Gpwebpay_Model_Config $config) {
		$this->_config = $config;
		return $this;
	}
}
