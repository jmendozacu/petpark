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
 * GPWebpay index controller
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_IndexController extends Mage_Core_Controller_Front_Action
{
	protected $_init;
	
	protected $_redirectBlockType = 'nostress_gpwebpay/redirect';
	protected $_returnBlockType  = 'nostress_gpwebpay/return';
	protected $_failureBlockType  = 'nostress_gpwebpay/failure';
	
	protected $_sendNewOrderEmail = true;
	
	protected $_order = null;
	protected $_paymentInst = null;
	
	protected $_transactionID = null;
	
	protected function _construct() {
		$this->_init = Mage::getModel('nostress_gpwebpay/abstract');
	}
	
	protected function _expireAjax() {
		$this->_init->GPLog(0);
		if (!$this->_init->getCheckout()->getQuote()->hasItems()) {
			$this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
			$this->_init->GPLog(1);
			exit;
		}
		$this->_init->GPLog(1);
	}
	
	/** DEPRECATED
	* Get singleton of Checkout Session Model
	*
	* @return Mage_Checkout_Model_Session
	*/
	public function getCheckout() {
		return $this->_init->getCheckout();
	}
	
	public function redirectAction() {
		/*
		$this->_init->GPLog(0);
		$session = $this->_init->getCheckout();
		
		$order = Mage::getModel('sales/order');
		$OrderId = $session->getLastRealOrderId();
		if (empty($OrderId)) {
			$this->_init->GPLog("OrderId is not set in \$session->getLastRealOrderId()");
			Mage::getSingleton('core/session')->addError($this->__("The order is no longer active or can not be found"));
			$this->_redirect('* /* /failure?error=orderid&');
		}
		else {
			$order->loadByIncrementId($OrderId);
			$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_HOLDED, $this->__('Customer was redirected to GPWebPay.'));
			$this->_init->GPLog("Order status set to \"On Hold\"");
			$this->_init->GPLog("Added status item to the order history (\"Customer was redirected to GPWebPay\")");
			$order->save();
			$this->_init->GPLog("The order was saved (\$order->save())");
			$html = $this->getLayout()->createBlock($this->_redirectBlockType)->setOrder($order)->toHtml();
			$this->getResponse()->setBody($html);
		}
		
		$this->_init->GPLog("\$OrderId: ".$OrderId, 1);
		$this->_init->GPLog(1);*/
		
		$this->getResponse()->setBody($this->getLayout()->createBlock($this->_redirectBlockType)->toHtml());
	}
	
	public function returnAction() {
		$this->_init->GPLog(0);
		$params = Mage::app()->getRequest()->getParams();
		$Log = $this->_init->initLog();
		$Log->setFunction("returnAction()");
		$Log->setParams(print_r($params, 1));
		$Log->setPrcode($params["PRCODE"]);
		$Log->setSrcode($params["SRCODE"]);
		
		$merchantId = $this->_init->getConfig()->getMerchantNumber();
		$OrderSuccess = 0;
		$invoice = null;
		
		$data = $params["OPERATION"]."|".$params["ORDERNUMBER"]."|".$params["PRCODE"]."|".$params["SRCODE"]."|".$params["RESULTTEXT"];
		$dataDigest1 = $data."|".$merchantId;
		
		$verifyDigest = $this->_init->verify($data, $params["DIGEST"]);
		$verifyDigest1 = $this->_init->verify($dataDigest1, $params["DIGEST1"]);
		
		if ($verifyDigest == "1" && $verifyDigest1 == "1") { // pouze overeni konzistence dat
			$this->_init->GPLog("Digest and Digest1 successfully checked");
			
			$order = Mage::getModel('sales/order');
			if (isset($params["ORDERNUMBER"]) && !empty($params["ORDERNUMBER"])) {
				$orderIncrementId = $params["ORDERNUMBER"];
			}
			else {
				$orderIncrementId = $this->_init->getCheckout()->getLastRealOrderId();
			}
			if (empty($orderIncrementId)) {
				Mage::getSingleton('core/session')->addError(Mage::helper('nostress_gpwebpay')->__("Wrong order id."));
				$this->_redirect('*/*/failure?error=notknown&');
			}
			$order->loadByIncrementId($orderIncrementId);
			$Log->setOrderid($orderIncrementId);
			
			$this->_init->saveLog($Log);
			
			$transaction = Mage::getModel('nostress_gpwebpay/transactions');
			/*$transaction->loadByOrderId($order->getIncrementId())
				->addStatusHistoryComment(Mage::helper('nostress_gpwebpay')->__("State updated automatically "), 'pending');
			$transaction->queryOrderState(false)
				->save();*/
			$transaction->loadByOrderId($order->getIncrementId())
				->queryOrderState(false)
				->save();
			/*if ($order->getStatus() !== Mage_Sales_Model_Order::STATE_PROCESSING) {
				$this->_init->GPLog("This order was already processed");
				Mage::getSingleton('core/session')->addError(Mage::helper('nostress_gpwebpay')->__("This order was already processed."));
				$this->_redirect('* /* /failure?error=notknown&');
			}*/
			
			if ($params["PRCODE"] == "0" && $params["SRCODE"] == "0") {
				$action = $this->_init->getConfig()->getPayAction();
				$OrderState = $transaction->getNewState(null, true);
				$this->_init->GPLog("OrderState: ".$OrderState." (".$this->_init->getStateName($OrderState).")");
				switch ($action) {
					case 1: // Authorize
						$this->_init->GPLog("Authorize mode");
						if ($OrderState == 4) {
							$OrderSuccess = 1;
							$message = "Payment authorization via GPWebPay was recived.";
						}
					break;
					case 2: //Deposit
						$this->_init->GPLog("Deposit mode");
						if ($OrderState == 7) {
							$OrderSuccess = 1;
							$message = "Payment deposit via GPWebPay was recived.";
						}
					break;
					default: //Authorize
						$this->_init->GPLog("Authorize mode");
						if ($OrderState == 4) {
							$OrderSuccess = 1;
							$message = "Payment authorization via GPWebPay was recived.";
						}
					break;
				}
				
				if ($OrderSuccess != 1) {
					$this->_init->GPLog("The order was not approved by GPWebPay");
					Mage::getSingleton('core/session')->addError(Mage::helper('nostress_gpwebpay')->__("The order was not approved by GPWebPay."));
					$order->addStatusHistoryComment(
							Mage::helper('nostress_gpwebpay')->__('The order was not approved by GPWebPay. Order state: %s (%s)', $OrderState, $this->_init->getStateName($OrderState)),
							Mage_Sales_Model_Order::STATE_HOLDED
						)
						->save();
					$this->_redirect('*/*/failure?error=notknown&');
				}
				else {
					$order->addStatusHistoryComment($this->__($message))
						->save();
					$this->_init->GPLog("Added comment to the order history (\"".$message."\")");
					
					$invoiceCollection = $order->getInvoiceCollection();
					$invoices = array();
					foreach ($invoiceCollection as $invoice) {
						$invoices[] = $invoice->getIncrementId();
					}
					
					if (empty($invoices)) {
						//$orderTransaction =  Mage::getModel('sales/order_payment_transaction');
						$invoice = $order->prepareInvoice();
						$payment = $order->getPayment();
						if ($order->canInvoice()) {
							$this->_init->GPLog("Can invoice");
							if ($action == 1) {
								$payment->authorize(false, $order->getGrandTotal());
								$payment->setAmountAuthorized($order->getTotalDue());
								//$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'pending_payment', '', false);
								/*$orderTransaction->setOrderPaymentObject($order->getPayment());
								//$orderTransaction->setTxnId($transaction->getRequestId());
								$orderTransaction->setTxnId($transaction->getId());
								$orderTransaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
								$orderTransaction->setAdditionalInformation('amt', $order->getTotalDue());
								$orderTransaction->setAdditionalInformation('store_id', $order->getStoreId());
								$orderTransaction->save();*/
							}
							elseif ($action == 2) { // If Deposit, set Paid status
								$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
								$transaction->setDeposited($order->getGrandTotal())
									->save();
							}
							$invoice->register();
							$transactionSave = Mage::getModel('core/resource_transaction')
								->addObject($invoice)
								->addObject($invoice->getOrder())
								->save();
							$invoice = Mage::getModel('sales/order_invoice')->load($invoice->getIncrementId());
							if ($invoice->getIncrementId()) {
								$order->addStatusHistoryComment(Mage::helper('nostress_gpwebpay')->__($message))
									->save();
							}
						}
						else {
							$this->_init->GPLog("Can not invoice");
						}
					}
					
					if ($order->getIncrementId() && $this->_sendNewOrderEmail) {
						$order->sendNewOrderEmail();
						if ($invoice != null) {
							$invoice->setEmailSent(true);
							//$invoice->save();
						}
						$this->_init->GPLog("Email to customer was sent");
					}
					
					// Clear the shopping cart
					foreach($this->_init->getCheckout()->getQuote()->getItemsCollection() as $item ) {
						Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
					}
					
					$this->_init->GPLog("Redirecting to checkout/onepage/success");
					$this->_redirect('checkout/onepage/success');
					//echo "<br /><br /><strong>NoRedirect</strong>";
				}
			}
			elseif ($params["PRCODE"] == "20") {
				Mage::getSingleton('core/session')->addError(Mage::helper('nostress_gpwebpay')->__("This order was already processed by the GPWebPay payment system."));
				$this->_init->GPLog("PRCODE: ".$params["PRCODE"]);
				$this->_init->GPLog("SRCODE: ".$params["SRCODE"]);
				$this->_redirect('*/*/failure?PRCODE='.$params["PRCODE"].'&SRCODE='.$params["SRCODE"].'&');
			}
			else {
				$errorMessage = $this->_init->getErrorMessage($params["PRCODE"], $params["SRCODE"]);
				$this->_init->GPLog("Error returned from the GPWebPay".$errorMessage);
				Mage::getSingleton('core/session')->addError(Mage::helper('nostress_gpwebpay')->__("Error occured in GPWebPay payment. %s", $errorMessage));
				$order->addStatusHistoryComment(
							Mage::helper('nostress_gpwebpay')->__('Error occured in GPWebPay payment. %s (PRCODE: %s, SRCODE: %s)', $errorMessage, $params["PRCODE"], $params["SRCODE"]),
							Mage_Sales_Model_Order::STATE_HOLDED
						)
						->save();
				$this->_init->GPLog("PRCODE: ".$params["PRCODE"]);
				$this->_init->GPLog("SRCODE: ".$params["SRCODE"]);
				$this->_redirect('*/*/failure?PRCODE='.$params["PRCODE"].'&SRCODE='.$params["SRCODE"].'&');
			}
		}
		else {
			/// TODO: Write error about digest not being correct!
		}
		$this->_init->GPLog(1);
	}
	
	public function failureAction() {
		$this->_init->GPLog(0);
		$session = $this->_init->getCheckout();
		$Log = $this->_init->initLog();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		
		$params = Mage::app()->getRequest()->getParams();
		
		if (isset($params["PRCODE"]) && isset($params["SRCODE"])) {
			$Log->setPrcode($params["PRCODE"]);
			$Log->setSrcode($params["SRCODE"]);
		}
		$Log->setOrderid($order->getRealOrderId());
		$Log->setFunction("failureAction()");
		$this->_init->saveLog($Log);
		
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock($this->_failureBlockType)->setOrder($order));
		$this->renderLayout();
		$this->_init->GPLog(1);
	}

}