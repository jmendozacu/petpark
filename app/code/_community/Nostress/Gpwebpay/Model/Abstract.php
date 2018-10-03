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
 * Abstract model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
	
	/**
	* unique internal payment method identifier
	* 
	* @var string [a-z0-9_]
	*/
	protected $_code = 'gpwebpay';
	
	/**
	* Config instance
	* @var Nostress_Gpwebpay_Model_Config
	*/
	protected $_config = null;
	
	protected $_formBlockType = 'nostress_gpwebpay/form';
	protected $_infoBlockType = 'nostress_gpwebpay/info';
	
	protected $_isGateway               = false;
	protected $_canAuthorize            = false;
	protected $_canCapture              = true;
	protected $_canCapturePartial       = false;
	protected $_canRefund               = true;
	protected $_canVoid                 = false;
	protected $_canUseInternal          = false;
	protected $_canUseCheckout          = true;
	protected $_canUseForMultishipping  = true;
	
	protected $_order;
	protected $_api;
	
	protected $_firePhp;
	
	const LOG_VARIABLES = false;
	const LOG_INFORMATIONAL = true;
	const LOG_CALLS = false;
	const LOG_FINISHES = false;
	
	public function __construct() {
		/*require_once("FirePHPCore/FirePHP.class.php");
		$firePhpOptions = array(
			'maxObjectDepth' => 5,
			'maxArrayDepth' => 5,
			'maxDepth' => 10,
			'useNativeJsonEncode' => true,
			'includeLineNumbers' => true
		);
		
		$this->_firePhp = FirePHP::getInstance(true);
		$this->_firePhp->setOptions($firePhpOptions);*/
		
		//require_once(Mage::getBaseDir('lib').DS.'NuSOAP'.DS.'nusoap.php');
	}
	
	public function fireLog($Message) {
		//$this->_firePhp->log($Message);
	}
	
	public function getOrderPlaceRedirectUrl() {
		$this->GPLog(0);
		$this->GPLog(1);
		return Mage::getUrl('gpwebpay/index/redirect');
	}
	
	public function createTransaction() {
		$transaction = Mage::getModel('nostress_gpwebpay/transactions');
		$orderIncrementId = $this->getOrder()->getIncrementId();
		$transaction->loadByOrderId($orderIncrementId);
		if (!$transaction->getId()) {
			$defaultState = $this->getConfig()->getDefaultState();
			$transaction->createTransaction($orderIncrementId, $defaultState, Mage::helper('nostress_gpwebpay')->__('Initial transaction creation'));
		}
	}
	
	/**
	* Return form field array
	*
	* @return array
	*/
	public function getGpwebpayFormFields() {
		$api = $this->getApi();
		$result = $api->getGpwebpayRequest();
		return $result;
	}
	
	public function getApi($order = null) {
		if (!$this->_api) {
			if ($order == null) {
				$order = $this->getOrder();
			}
			$orderIncrementId = $order->getIncrementId();
			
			$this->_api = Mage::getModel('nostress_gpwebpay/api_gpwebpay')
				->setConfigObject($this->getConfig())
				->setOrderId($orderIncrementId)
				->setCurrencyCode($order->getBaseCurrencyCode())
				->setOrder($order)
				->setReturnUrl(Mage::getUrl('*/*/return'));
		}
		
		return $this->_api;
	}
	
	public function canUseCheckout() {
		$this->GPLog(0);
		
		$Date = Mage::getModel('core/date')->timestamp(time());
		$Date = strtotime(date('m/d/Y', $Date));
		
		$Config = $this->getConfig()->getTemporaryShutdown();
		//$Config = @unserialize(Mage::getStoreConfig('payment/gpwebpay/temp_shutdown'));
		if (isset($Config["active"]) && $Config["active"] == 1) {
			$this->GPLog("Temporary shutdown is active");
			$DateFrom = strtotime($Config[0]);
			$DateTo = strtotime($Config[1]);
			if ($Date >= $DateFrom && $Date <= $DateTo) {
				$this->GPLog("Temporary shutdown is in charge");
				$this->GPLog(1);
				return false;
			}
			else {
				$this->GPLog("Temporary shutdown is not in charge");
			}
		}
		else {
			$this->GPLog("Temporary shutdown is disabled");
		}
		
		$this->GPLog(1);
		return Mage::helper('nostress_gpwebpay/version')->isModuleValid();
	}
	
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}
	
	public function getOrder() {
		$this->GPLog(0);
		$orderIncrementId = "";
		if (!$this->_order) {
			$orderIncrementId = $this->getCheckout()->getLastRealOrderId();
			$this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		}
		else {
			$orderIncrementId = $this->_order->getIncrementId();
		}
		/*if (!$this->_order) {
			$paymentInfo = $this->getInfoInstance();
			$this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);
			//$this->GPLog("\$paymentInfo: ".$paymentInfo, 1);
		}*/
		$this->GPLog("\$orderNumber: ".$orderIncrementId, 1);
		$this->GPLog(1);
		return $this->_order;
	}
	
	/**
	* Return redirect block type
	*
	* @return string
	*/
	public function getRedirectBlockType() {
		$this->GPLog(0);
		$this->GPLog(1);
		return $this->_redirectBlockType;
	}
	
	/**
	* Return payment method type string
	*
	* @return string
	*/
	public function getPaymentMethodType() {
		$this->GPLog(0);
		$this->GPLog(1);
		return $this->_paymentMethod;
	}
	
	public function getPrivateCert() {
		$this->GPLog(0);
		$this->GPLog(1);
		return Mage::getBaseDir().DIRECTORY_SEPARATOR."var".DIRECTORY_SEPARATOR."gpwebpay".DIRECTORY_SEPARATOR.$this->getConfigData('folder').DIRECTORY_SEPARATOR.$this->getConfigData('private');
	}
	
	public function getPublicCert() {
		$this->GPLog(0);
		$this->GPLog(1);
		return Mage::getBaseDir().DIRECTORY_SEPARATOR."var".DIRECTORY_SEPARATOR."gpwebpay".DIRECTORY_SEPARATOR.$this->getConfigData('folder').DIRECTORY_SEPARATOR.$this->getConfigData('public');
	}
	
	public function getPrivatePass() {
		$this->GPLog(0);
		$this->GPLog(1);
		return $this->getConfigData('privatepass');
	}
	
	public function getOrderStatus() {
		$this->GPLog(0);
		$this->GPLog(1);
		return $this->getConfigData('order_status');
	}
	
	public function getCustomText() {
		$this->GPLog(0);
		$this->GPLog(1);
		return nl2br($this->getConfigData('customtext'));
	}
	
	public function sign($text, $SOAP = 0) {
		$this->GPLog(0);
		$privatni = $this->getPrivateCert();
		$heslo = $this->getPrivatePass();
		$signature = "";
		$Success = 0;
		
		try {
			$fp = fopen($privatni, "r");
			if ($fp == false) {
				throw new Exception("Private key does not exist or you do not have the permissions to read it");
			}
			else {
				$this->privatni = fread($fp, filesize($privatni));
				if ($this->privatni == false) {
					throw new Exception("Private key can not be read. Please, check it and try again");
				}
				fclose($fp);
			}
			$Success = 1;
			$this->GPLog("Private key was loaded");
		}
		catch (Exception $e) {
			$this->GPLog("Private key was not loaded: ".$e->getMessage());
			return 0;
		}
		$this->heslo = $heslo;
		
		if ($Success == 1) {
			try {
				$pkeyid = openssl_get_privatekey($this->privatni, $this->heslo);
				openssl_sign($text, $signature, $pkeyid);
				$signature = base64_encode($signature);
				openssl_free_key($pkeyid);
				$this->GPLog("Signature for private key was created");
			}
			catch (Exception $e) {
				$this->GPLog("Signature for private key was NOT created: ".$e->getMessage());
				return 1;
			}
		}
		else {
			$this->GPLog("Signature for private key was NOT created because there was a problem with loading the private key");
			return 1;
		}
		
		$this->GPLog(1);
		if ($SOAP == 0) {
			return urlencode($signature);
		}
		else {
			return $signature;
		}
	}
	
	public function verify($text, $signature) {
		$this->GPLog(0);
		$verejny = $this->getPublicCert();
		$vysledek = "";
		$Success = 0;
		
		try {
			$fp = fopen($verejny, "r");
			if ($fp == false) {
				throw new Exception("Public key does not exist or you do not have the permissions to read it");
			}
			else {
				$this->verejny = fread($fp, filesize($verejny));
				if ($this->verejny == false) {
					throw new Exception("Public key can not be read. Please, check it and try again");
				}
				fclose($fp);
			}
			$Success = 1;
			$this->GPLog("Public key was loaded");
		}
		catch (Exception $e) {
			$this->GPLog("Public key was not loaded: ".$e->getMessage());
			return 0;
		}
		
		if ($Success == 1) {
			try {
				$pubkeyid = openssl_get_publickey($this->verejny);
				$decoded_signature = base64_decode($signature);
				$vysledek = openssl_verify($text, $decoded_signature, $pubkeyid);
				openssl_free_key($pubkeyid);
				$this->GPLog("Public key was verified");
			}
			catch (Exception $e) {
				$this->GPLog("Public key was not verified: ".$e->getMessage());
				return 1;
			}
		}
		else {
			$this->GPLog("Public key was NOT verified because there was a problem with loading the public key");
			return 1;
		}
		
		// logovani volani funkce verify()  
		$gpwebpay = Mage::getModel('nostress_gpwebpay/gpwebpay');
		$gpwebpay->setVerifyId(null);
		$gpwebpay->setTime(date("Y-m-d H:j:s", time()));
		$gpwebpay->setString($text);
		$gpwebpay->setDigest($signature);
		$gpwebpay->setResult($vysledek);
		$this->GPLog("Information for log about the verification was created");
		
		try {
			$gpwebpay->save();
			$this->GPLog("Log about the verification was saved into the database");
		}
		catch (Exception $e) {
			$this->GPLog("Log about the verification was NOT saved into the database: ".$e->getMessage());
		}
		
		$this->GPLog(1);
		return $vysledek;
	}
	
	public function soapAction($Parameters) {
		$this->GPLog(0);
		$Return = array();
		$Return["error_code"] = 0;
		$Return["error"] = "";
		$resultCode = null;
		$params = array();
		$Log = $this->initLog();
		if (((isset($Parameters["real_order_id"]) && !empty($Parameters["real_order_id"])) || $Parameters["action"] == "batchClose") && isset($Parameters["action"]) && !empty($Parameters["action"])) {
			$action = $Parameters["action"];
			if ($action != "batchClose") {
				$realOrderId = $Parameters["real_order_id"];
				$Log->setOrderid($realOrderId);
			}
			else {
				$Log->setOrderid("Multiple orders");
			}
			$Log->setFunction("soapAction(".print_r($Parameters, 1).")");
			$soapUrl = $this->getConfig()->getSoapUrl();
			//$soapClient = new nusoap_client($soapUrl, false);
			
			try {
				$soapClient = new SoapClient(null, array(
					'location' => $soapUrl,
					'uri' => $soapUrl));
			} catch (SoapFault $error) {
				$Return["error_code"] = 4;
				$Return["error_string"] = $error->faultstring;
			}
			
			if (!isset($soapClient) || !$soapClient) {
				$Return["error_code"] = 99;
			}
			/*elseif ($soapClient->getError()) {
				$Return["error_code"] = 4;
			}*/
			switch ($action) {
				case "deposit":
				case "credit":
					if (isset($Parameters["amount"]) && !empty($Parameters["amount"]) && $Parameters["amount"] != 0) {
						$orderAmount = $Parameters["amount"];
						$Data = $this->getConfig()->getMerchantNumber()."|".$realOrderId."|".$orderAmount;
						//$orderAmount = new soapval('amount', 'long', $orderAmount);
						$orderAmount = new SoapVar($orderAmount, XSD_LONG, "xsd:long");
						$params = array(
							$this->getConfig()->getMerchantNumber(),
							$realOrderId,
							$orderAmount
						);
					}
					else {
						$Return["error_code"] = 2;
					}
				break;
				case "creditReversal":
					if (isset($Parameters["credit_number"]) && !empty($Parameters["credit_number"]) && $Parameters["credit_number"] != 0) {
						$creditNumber = $Parameters["credit_number"];
						$Data = $this->getConfig()->getMerchantNumber()."|".$realOrderId."|".$creditNumber;
						$params = array(
							$this->getConfig()->getMerchantNumber(),
							$realOrderId,
							$creditNumber
						);
					}
					else {
						$Return["error_code"] = 2;
					}
				break;
				case "batchClose":
					$Data = $this->getConfig()->getMerchantNumber();
					$params = array(
						$this->getConfig()->getMerchantNumber()
					);
				break;
				default:
					$Data = $this->getConfig()->getMerchantNumber()."|".$realOrderId;
					$params = array(
						$this->getConfig()->getMerchantNumber(),
						$realOrderId
					);
				break;
			}
			$Digest = $this->sign($Data, 1);
			$Log->setDatasent($Data);
			$Log->setDigest($Digest);
			array_push($params, $Digest);
			//Mage::log($Digest);
			if ($Digest === 0) {
				$Return["error_code"] = 5;
			}
			elseif ($Digest == 1) {
				$Return["error_code"] = 6;
			}
			
			if ($Return["error_code"] == 0) {
				//$resultCode = (object)$soapClient->call($action, $params);
				try {
					$resultCode = (object)$soapClient->__soapCall($action, $params);
				} catch (SoapFault $error) {
					$Return["error_code"] = 4;
					$Return["error_string"] = $error->faultstring;
					//trigger_error("SOAP Fault: (faultcode: {$error->faultcode}, faultstring: {$error->faultstring})", E_USER_ERROR);
				}
				/*if ($soapClient->getError()) {
					$Return["error_code"] = 4;
				}*/
			}
			
			if ($Return["error_code"] == 0) {
				//Mage::log(print_r($resultCode,1));
				
				$Log->setReturn(print_r($resultCode, 1));
				switch ($action) {
					case "queryOrderState":
						$DigestReturn = $resultCode->orderNumber."|".$resultCode->state."|".$resultCode->primaryReturnCode."|".$resultCode->secondaryReturnCode;
					break;
					case "batchClose":
						$DigestReturn = $resultCode->primaryReturnCode."|".$resultCode->secondaryReturnCode;
					break;
					default:
						$DigestReturn = $resultCode->orderNumber."|".$resultCode->primaryReturnCode."|".$resultCode->secondaryReturnCode;
					break;
				}
				$Verify = $this->verify($DigestReturn, $resultCode->digest);
				
				$this->saveLog($Log);
				
				if ($Verify == "1") {
					if (isset($resultCode->primaryReturnCode)) {
						$Return["primary_return_code"] = $resultCode->primaryReturnCode;
						if ((int)$resultCode->primaryReturnCode !== 0) {
							$Return["error_code"] = 3;
						}
					}
					else {
						$Return["primary_return_code"] = "1000";
						$Return["error_code"] = 3;
					}
					if (isset($resultCode->secondaryReturnCode)) {
						$Return["secondary_return_code"] = $resultCode->secondaryReturnCode;
						if ((int)$resultCode->secondaryReturnCode !== 0) {
							$Return["error_code"] = 3;
						}
					}
					else {
						$Return["secondary_return_code"] = "0";
						$Return["error_code"] = 3;
					}
					if (isset($resultCode->digest)) {
						$Return["digest"] = $resultCode->digest;
					}
					else {
						$Return["digest"] = "";
					}
					if (isset($resultCode->ok)) {
						$Return["ok"] = (boolean)$resultCode->ok;
					}
					else {
						$Return["ok"] = false;
					}
					if (isset($resultCode->orderNumber)) {
						$Return["real_order_id"] = $resultCode->orderNumber;
					}
					else {
						$Return["real_order_id"] = "";
					}
					if (isset($resultCode->state)) {
						$Return["state"] = $resultCode->state;
					}
					else {
						$Return["state"] = "";
					}
					if (isset($resultCode->requestId)) {
						$Return["request_id"] = $resultCode->requestId;
					}
					else {
						$Return["request_id"] = "";
					}
				}
				else {
					$Return["error_code"] = 1;
				}
			}
		}
		else {
			$Return["error_code"] = 2;
		}
		//Mage::log($Return["error_code"]);
		if ($Return["error_code"] != 0) {
			switch ($Return["error_code"]) {
				case 1:
					$Return["error"] = Mage::helper('nostress_gpwebpay')->__('Please, check your certificates and GPWebpay settings.');
				break;
				
				case 2:
					$Return["error"] = Mage::helper('nostress_gpwebpay')->__('Bad parameters.');
				break;
				
				case 3:
					//$Return["error"] = Mage::getModel('nostress_gpwebpay/abstract')->getErrorMessage($Return["primary_return_code"], $Return["secondary_return_code"]);
					$Return["error"] = $this->getErrorMessage($Return["primary_return_code"], $Return["secondary_return_code"]);
				break;
				
				case 4:
					//$Return["error"] = Mage::helper('nostress_gpwebpay')->__('SOAP error: %s', $soapClient->getError());
					$Return["error"] = Mage::helper('nostress_gpwebpay')->__('SOAP error: %s', $Return["error_string"]);
				break;
				
				case 5:
					$Return["error"] = Mage::helper('nostress_gpwebpay')->__('File with private certificate could not been found.');
				break;
				
				case 6:
					$Return["error"] = Mage::helper('nostress_gpwebpay')->__('Password for the private certificate is wrong.');
				break;
				
				default:
					$Return["error"] = Mage::helper('nostress_gpwebpay')->__('Unknown error occured.');
				break;
			}
		}
		
		$this->GPLog(1);
		return $Return;
	}
	
	public function getStateName($stateId) {
		switch ($stateId) {
			case 1:
				return "requested";
			break;
			case 2:
				return "pending";
			break;
			case 3:
				return "created";
			break;
			case 4:
				return "approved";
			break;
			case 5:
				return "approve_reversed";
			break;
			case 6:
				return "unapproved";
			break;
			case 7:
				return "deposit_batch_opened";
			break;
			case 8:
				return "deposit_batch_closed";
			break;
			case 9:
				return "order_closed";
			break;
			case 10:
				return "deleted";
			break;
			case 11:
				return "credited_batch_opened";
			break;
			case 12:
				return "credited_batch_closed";
			break;
			case 13:
				return "declined";
			break;
			default:
				return "requested";
			break;
		}
		return "requested";
	}
	
	public function getErrorMessage($primaryReturnCode, $secondaryReturnCode) {
		//Mage::log($primaryReturnCode);
		$srcodes[1] = array(
			"0" => "",
			"1" => "'".Mage::helper('nostress_gpwebpay')->__('order id')."'",
			"2" => "'".Mage::helper('nostress_gpwebpay')->__('merchant number')."'",
			"6" => "'".Mage::helper('nostress_gpwebpay')->__('amount')."'",
			"7" => "'".Mage::helper('nostress_gpwebpay')->__('currency')."'",
			"8" => "'".Mage::helper('nostress_gpwebpay')->__('deposit flag')."'",
			"10" => "'".Mage::helper('nostress_gpwebpay')->__('merordernum')."'",
			"11" => "'".Mage::helper('nostress_gpwebpay')->__('credit number')."'",
			"12" => "'".Mage::helper('nostress_gpwebpay')->__('operation')."'",
			"18" => "'".Mage::helper('nostress_gpwebpay')->__('batch')."'",
			"22" => "'".Mage::helper('nostress_gpwebpay')->__('order')."'",
			"24" => "'".Mage::helper('nostress_gpwebpay')->__('url')."'",
			"25" => "'".Mage::helper('nostress_gpwebpay')->__('md')."'",
			"26" => "'".Mage::helper('nostress_gpwebpay')->__('description')."'",
			"34" => "'".Mage::helper('nostress_gpwebpay')->__('digest')."'"
		);
		$srcodes[2] = array(
			"3000" => Mage::helper('nostress_gpwebpay')->__('Cardholder not authenticated in 3D'),
			"3001" => Mage::helper('nostress_gpwebpay')->__('Authenticated'),
			"3002" => Mage::helper('nostress_gpwebpay')->__('Issuer or Cardholder not anticipating in 3D'),
			"3004" => Mage::helper('nostress_gpwebpay')->__('Issuer not participating or Cardholder not enrolled'),
			"3005" => Mage::helper('nostress_gpwebpay')->__('Technical problem during Cardholder authentication'),
			"3006" => Mage::helper('nostress_gpwebpay')->__('Technical problem during Cardholder authentication'),
			"3007" => Mage::helper('nostress_gpwebpay')->__('Acquirer technical problem. Contact the merchant'),
			"3008" => Mage::helper('nostress_gpwebpay')->__('Unsupported card product')
		);
		$srcodes[3] = array(
			"1001" => Mage::helper('nostress_gpwebpay')->__('Unsuccessful authorization - blocked card'),
			"1002" => Mage::helper('nostress_gpwebpay')->__('Authorization declined'),
			"1003" => Mage::helper('nostress_gpwebpay')->__('Unsuccessful authorization - card problem'),
			"1004" => Mage::helper('nostress_gpwebpay')->__('Unsuccessful authorization - technical problem in authorization proccess'),
			"1005" => Mage::helper('nostress_gpwebpay')->__('Unsuccessful authorization - Account problem')
		);
		$srcodes["values"] = array(
			"1" => $srcodes[1],
			"2" => $srcodes[1],
			"3" => $srcodes[1],
			"4" => $srcodes[1],
			"5" => $srcodes[1],
			"15" => $srcodes[1],
			"20" => $srcodes[1],
			"28" => $srcodes[2],
			"30" => $srcodes[3]
		);
		if ($primaryReturnCode == "0" && $secondaryReturnCode == "0") {
			return Mage::helper('nostress_gpwebpay')->__('No error occured');
		}
		
		if ($primaryReturnCode == "1") {
			return Mage::helper('nostress_gpwebpay')->__('Field %s is too long', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "2") {
			return Mage::helper('nostress_gpwebpay')->__('Field %s is too short', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "3") {
			return Mage::helper('nostress_gpwebpay')->__('Incorrect content of field %s', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "4") {
			return Mage::helper('nostress_gpwebpay')->__('Field %s is null', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "5") {
			return Mage::helper('nostress_gpwebpay')->__('Missing required field %s', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "6") {
			return Mage::helper('nostress_gpwebpay')->__('Field not exists');
		}
		if ($primaryReturnCode == "11") {
			return Mage::helper('nostress_gpwebpay')->__('Unknown merchant');
		}
		if ($primaryReturnCode == "14") {
			return Mage::helper('nostress_gpwebpay')->__('Duplicite order number');
		}
		if ($primaryReturnCode == "15") {
			return Mage::helper('nostress_gpwebpay')->__('Object with given %s not found', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "17") {
			return Mage::helper('nostress_gpwebpay')->__('Amount to deposit exceeds approved amount');
		}
		if ($primaryReturnCode == "18") {
			return Mage::helper('nostress_gpwebpay')->__('Total sum of credited amounts exceeded deposited amount');
		}
		if ($primaryReturnCode == "20") {
			return Mage::helper('nostress_gpwebpay')->__('Object %s not in valid state for operation', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "25") {
			return Mage::helper('nostress_gpwebpay')->__('Operation not allowed for user');
		}
		if ($primaryReturnCode == "26") {
			return Mage::helper('nostress_gpwebpay')->__('Technical problem in connection to authorization center');
		}
		if ($primaryReturnCode == "27") {
			return Mage::helper('nostress_gpwebpay')->__('Incorrect order type');
		}
		if ($primaryReturnCode == "28") {
			return Mage::helper('nostress_gpwebpay')->__('Declined in 3D. Reason: %s', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "30") {
			return Mage::helper('nostress_gpwebpay')->__('Declined in authorization center. Reason: %s', $srcodes["values"][$primaryReturnCode][$secondaryReturnCode]);
		}
		if ($primaryReturnCode == "31") {
			return Mage::helper('nostress_gpwebpay')->__('Wrong digest');
		}
		if ($primaryReturnCode == "35") {
			return Mage::helper('nostress_gpwebpay')->__('Session expired');
		}
		if ($primaryReturnCode == "1000") {
			return Mage::helper('nostress_gpwebpay')->__('Technical problem');
		}
		
		return Mage::helper('nostress_gpwebpay')->__('Unknown error occured');
	}
	
	public function getConfig() {
		if (null === $this->_config) {
			$params = array($this->_code);
			if ($store = $this->getStore()) {
				$params[] = is_object($store) ? $store->getId() : $store;
			}
			$this->_config = Mage::getModel('nostress_gpwebpay/config', $params);
		}
		return $this->_config;
	}
	
	public function getCreateOrderUrl() {
		$this->GPLog(0);
		$Log = $this->initLog();
		$Log->setFunction("getCreateOrderUrl()");
		
		$merchantNumber = $this->getConfig()->getMerchantNumber();
		$operation = $this->getConfig()->getOperation();
		$orderIncrementId = $this->getOrder()->getIncrementId();
		$Log->setOrderid($orderIncrementId);
		$orderUrl = $this->getConfig()->getOrderUrl();
		$Log->setUrl($orderUrl);
		$currency = $this->getApi()->getOrderCurrency();
		$amount = $this->getApi()->getOrderAmount();
		$depositFlag = $this->getConfig()->getDepositFlag();
		$backUrl = $this->getApi()->getReturnUrl();
		$to_digest = $this->getApi()->getDigestData();
		$Log->setDatasent($to_digest);
		$digest = $this->sign($to_digest);
		$Log->setDigest($digest);
		
		$finalUrl = $orderUrl."?";
		
		$finalUrl .= "MERCHANTNUMBER=".$merchantNumber."&amp;";
		$finalUrl .= "OPERATION=".$operation."&amp;";
		$finalUrl .= "ORDERNUMBER=".$orderIncrementId."&amp;";
		$finalUrl .= "AMOUNT=".$amount."&amp;";
		$finalUrl .= "CURRENCY=".$currency."&amp;";
		$finalUrl .= "DEPOSITFLAG=".$depositFlag."&amp;";
		$finalUrl .= "URL=".$backUrl."&amp;";
		$finalUrl .= "DIGEST=".$digest;
		
		$Log->setUrl($finalUrl);
		$this->saveLog($Log);
		
		$this->GPLog(1);
		return $finalUrl;
	}
	
	public function initLog() {
		$Log = Mage::getModel('nostress_gpwebpay/log');
		$Log->setLogId(null);
		$Log->setTime(time());
		
		return $Log;
	}
	
	public function saveLog($Log) {
		try {
			$Log->save();
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("Log about transaction errors was saved into the database");
		} catch ( Exception $e ) {
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("Log about transaction errors was NOT saved into the database: ".$e->getMessage());
		}
	}
	
	/**
	*  Function for logging
	*  
	*  @param $String String to log
	*                 0 = "Called"
	*                 1 = "Finished"
	*  @param $Type 0 = Informational
	*               1 = Variable
	*/
	public function GPLog($String, $Type = 0) {
		if (($Type == 1 && self::LOG_VARIABLES === false) || ($Type == 0 && self::LOG_INFORMATIONAL === false)) {
			return;
		}
		$Debug = debug_backtrace();
		$File = explode("/Nostress/Gpwebpay", $Debug[0]["file"]);
		$File = $File[1];
		
		if ($String === 0 && self::LOG_CALLS === true) {
			$String = "Called";
		}
		elseif ($String === 1 && self::LOG_FINISHES === true) {
			$String = "Finished";
		}
		elseif ($String === 1 || $String === 0) {
			return;
		}
		
		Mage::log($File."::".$Debug[1]["function"]."(): ".$String, null, "nostress_gpwebpay.log");
	}
}