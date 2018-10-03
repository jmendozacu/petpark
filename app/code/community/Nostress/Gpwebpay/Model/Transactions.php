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
 * Transaction model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Transactions extends Nostress_Gpwebpay_Model_TransactionsAbstract
{
	const ENTITY                      = 'transactions';
	
	/**
	* Transaction states
	*/
	const STATE_APPROVED              = 'approved';
	const STATE_APPROVE_REVERSED      = 'approve_reversed';
	const STATE_CREATED               = 'created';
	const STATE_CREDITED_BATCH_CLOSED = 'credited_batch_closed';
	const STATE_CREDITED_BATCH_OPENED = 'credited_batch_opened';
	const STATE_DECLINED              = 'declined';
	const STATE_DELETED               = 'deleted';
	const STATE_DEPOSIT_BATCH_CLOSED  = 'deposit_batch_closed';
	const STATE_DEPOSIT_BATCH_OPENED  = 'deposit_batch_opened';
	const STATE_ORDER_CLOSED          = 'order_closed';
	const STATE_PENDING               = 'pending';
	const STATE_REQUESTED             = 'requested';
	const STATE_UNAPPROVED            = 'unapproved';    
	
	/**
	* Transaction flags
	*/
	const ACTION_FLAG_APPROVE_REVERSAL  = 'approve_reversal';
	const ACTION_FLAG_DEPOSIT           = 'deposit';
	const ACTION_FLAG_DEPOSIT_REVERSAL  = 'deposit_reversal';
	const ACTION_FLAG_CREDIT            = 'credit';
	const ACTION_FLAG_CREDIT_REVERSAL   = 'credit_reversal';
	const ACTION_FLAG_ORDER_CLOSE       = 'order_close';
	const ACTION_FLAG_DELETE            = 'delete';
	const ACTION_FLAG_QUERY_ORDER_STATE = 'query_order_state';
	const ACTION_FLAG_BATCH_CLOSE       = 'query_order_state';
	
	/**
	* Identifier for history item
	*/
	const HISTORY_ENTITY_NAME = 'transactions';
	
	protected $_eventPrefix = 'nostress_gpwebpay_transactions';
	protected $_eventObject = 'transactions';
	
	protected $_statusHistory   = null;
	protected $_transactions    = null;
	protected $_relatedObjects  = array();
	protected $_requestId       = "";
	
	/**
	* Array of action flags for canUnhold, canEdit, etc.
	*
	* @var array
	*/
	protected $_actionFlag = array();
	
	/**
	* Identifier for history item
	*
	* @var string
	*/
	protected $_historyEntityName = self::HISTORY_ENTITY_NAME;
	
	/**
	* Initialize resource model
	*/
	protected function _construct() {
		$this->_init('nostress_gpwebpay/transactions');
	}
	
	/**
	* Init mapping array of short fields to
	* its full names
	*
	* @return Varien_Object
	*/
	protected function _initOldFieldsMap() {
		$this->_oldFieldsMap = Mage::helper('nostress_gpwebpay')->getOldFieldMap('transactions');
		return $this;
	}
	
	/**
	* Clear transaction object data
	*
	* @param string $key data key
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function unsetData($key=null) {
		parent::unsetData($key);
		return $this;
	}
	
	/**
	* Retrieve can flag for action (edit, unhold, etc..)
	*
	* @param string $action
	* @return boolean|null
	*/
	public function getActionFlag($action) {
		if (isset($this->_actionFlag[$action])) {
			return $this->_actionFlag[$action];
		}
		return null;
	}
	
	/**
	* Set can flag value for action (edit, unhold, etc...)
	*
	* @param string $action
	* @param boolean $flag
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function setActionFlag($action, $flag) {
		$this->_actionFlag[$action] = (boolean) $flag;
		return $this;
	}
	
	/**
	* Load transaction by order increment identifier
	*
	* @param string $incrementId
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function loadByOrderId($incrementId) {
		return $this->loadByAttribute('real_order_id', $incrementId);
	}
	
	/**
	* Load transaction by custom attribute value. Attribute value should be unique
	*
	* @param string $attribute
	* @param string $value
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function loadByAttribute($attribute, $value) {
		$this->load($value, $attribute);
		return $this;
	}
	
	public function getOrder() {
		return Mage::getModel('sales/order')->load($this->getOrderId());
	}
	
	/**
	* Retrieve store model instance
	*
	* @return Mage_Core_Model_Store
	*/
	public function getStore() {
		return $this->getOrder()->getStore();
	}
	
	/**
	* Retrieve Request Id
	*
	* @return string
	*/
	public function getRequestId() {
		return $this->_requestId;
	}
	
	public function getNewState($realOrderId = null, $numeric = false) {
		if (empty($realOrderId)) {
			$realOrderId = $this->getRealOrderId();
		}
		
		$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
			"action" => "queryOrderState",
			"real_order_id" => $realOrderId
		));
		//Mage::log(print_r($request, 1));
		if (!empty($request["error"])) {
			Mage::getSingleton('adminhtml/session')->addError(
				Mage::helper('nostress_gpwebpay')->__('State of the transaction could not have been requested. %s', $request["error"])
			);
			return $this->getState();
		}
		
		if (!empty($request["request_id"])) {
			$this->_requestId = $request["request_id"];
		}
		
		if ($numeric === true) {
			return (int)$request["state"];
		}
		else {
			return Mage::getModel('nostress_gpwebpay/abstract')->getStateName((int)$request["state"]);
		}
	}
	
	/**
	* Retrieve transaction approve reversal availability
	*
	* @return bool
	*/
	public function canApproveReversal() {
		$state = $this->getState();
		if ($this->isApproveReversed() || $state === self::STATE_APPROVE_REVERSED) {
			return false;
		}

		if ($this->getActionFlag(self::ACTION_FLAG_APPROVE_REVERSAL) === false) {
			return false;
		}
		
		if ($this->isApproved() || $state === self::STATE_APPROVED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve transaction deposit availability
	*
	* @return bool
	*/
	public function canDeposit() {
		$state = $this->getState();
		if ($this->isDepositedOpen() || $this->isDepositedClosed() || $state === self::STATE_DEPOSIT_BATCH_OPENED || $state === self::STATE_DEPOSIT_BATCH_CLOSED) {
			return false;
		}

		if ($this->getActionFlag(self::ACTION_FLAG_DEPOSIT) === false) {
			return false;
		}
		
		if ($this->isApproved() || $state === self::STATE_APPROVED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve reverse of the transaction deposit availability
	*
	* @return bool
	*/
	public function canDepositReversal() {
		$state = $this->getState();
		if ($this->isDepositReversed() || $state === self::STATE_APPROVED) {
			return false;
		}

		if ($this->getActionFlag(self::ACTION_FLAG_DEPOSIT_REVERSAL) === false) {
			return false;
		}
		
		if ($this->isDepositedOpen() || $state === self::STATE_DEPOSIT_BATCH_OPENED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve transaction credit availability
	*
	* @return bool
	*/
	public function canCredit() {
		$state = $this->getState();

		if ($this->getActionFlag(self::ACTION_FLAG_CREDIT) === false) {
			return false;
		}
		
		if ($this->getCredited() == $this->getOrder()->getBaseGrandTotal()) {
			return false;
		}
		
		if ($this->isCredited() /*|| $state === self::STATE_CREDITED_BATCH_OPENED */|| $state === self::STATE_CREDITED_BATCH_CLOSED || $this->isDepositedClosed() || $state === self::STATE_DEPOSIT_BATCH_CLOSED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve transaction credit reverse availability
	*
	* @return bool
	*/
	public function canCreditReversal() {
		$state = $this->getState();
		
		if ($this->getActionFlag(self::ACTION_FLAG_CREDIT_REVERSAL) === false) {
			return false;
		}
		
		if ($this->getCredited() == 0) {
			return false;
		}
		
		if ($this->isCreditReversed() || $state === self::STATE_CREDITED_BATCH_OPENED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve transaction close availability
	*
	* @return bool
	*/
	public function canOrderClose() {
		$state = $this->getState();

		if ($this->getActionFlag(self::ACTION_FLAG_ORDER_CLOSE) === false) {
			return false;
		}
		
		if ($this->isCreditedOpen() /*|| $state === self::STATE_CREDITED_BATCH_OPENED*/ || $state === self::STATE_CREDITED_BATCH_CLOSED || $this->isDepositedClosed() /*|| $state === self::STATE_DEPOSIT_BATCH_OPENED*/ || $state === self::STATE_DEPOSIT_BATCH_CLOSED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve transaction delete availability
	*
	* @return bool
	*/
	public function canDelete() {
		$state = $this->getState();

		if ($this->getActionFlag(self::ACTION_FLAG_DELETE) === false) {
			return false;
		}
		
		if (/*$state === self::STATE_REQUESTED || $state === self::STATE_PENDING || */$state === self::STATE_DECLINED || $state === self::STATE_UNAPPROVED || $this->isApproveReversed() || $state === self::STATE_APPROVE_REVERSED || $state === self::STATE_ORDER_CLOSED) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Retrieve transaction query state availability
	*
	* @return bool
	*/
	public function canQueryOrderState() {
		$state = $this->getState();

		if ($this->getActionFlag(self::ACTION_FLAG_QUERY_ORDER_STATE) === false) {
			return false;
		}
		
		if ($state === self::STATE_DELETED) {
			return false;
		}
		
		return true;
	}
	
	/**
	* Retrieve transactions batch close availability
	*
	* @return bool
	*/
	public function canBatchClose() {
		if ($this->getActionFlag(self::ACTION_FLAG_BATCH_CLOSE) === false) {
			return false;
		}
		
		$this->_transactions = Mage::getResourceModel('nostress_gpwebpay/transactions_collection')
			->setOrder('created_at', 'desc')
			->setOrder('entity_id', 'desc');
		
		foreach ($this->_transactions as $transaction) {
			if ($transaction->isCreditedOpen() || $transaction->isDepositedOpen()) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	* Retrieve transactions configuration model
	*
	* @return Nostress_Gpwebpay_Model_Transactions_Config
	*/
	public function getConfig() {
		return Mage::getSingleton('nostress_gpwebpay/transactions_config');
	}
	
	public function createTransaction($orderId, $state = null, $comment = "") {
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);
		$comment = Mage::helper('nostress_gpwebpay')->__('Created new transaction');
		$this->setData('order_id', $order->getData('entity_id'))
			->setData('real_order_id', $order->getData('increment_id'));
		if ($state != null) {
			$this->_setState($state, true, $comment);
		}
		else {
			$this->_setState($this->getNewState($order->getData('increment_id')), true, $comment);
		}
		$this->save();
	}
	
	/**
	* Transaction state setter.
	* If status is specified, will add transaction status history with specified comment
	* the setData() cannot be overriden because of compatibility issues with resource model
	*
	* @param string $state
	* @param string|bool $status
	* @param string $comment
	* @param bool $isCustomerNotified
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function setState($state, $status = false, $comment = '', $isCustomerNotified = null) {
		return $this->_setState($state, $status, $comment, $isCustomerNotified, true);
	}
	
	/**
	* Transaction state protected setter.
	* By default allows to set any state. Can also update status to default or specified value
	*
	* @param string $state
	* @param string|bool $status
	* @param string $comment
	* @param bool $isCustomerNotified
	* @param $shouldProtectState
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	protected function _setState($state, $status = false, $comment = '', $isCustomerNotified = null, $shouldProtectState = false) {
		// attempt to set the specified state
		if ($shouldProtectState) {
			if ($this->isStateProtected($state)) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__("The Transaction State '%s' must not be set manually.", $state));
			}
		}
		$this->setData('state', $state);
		
		// add status history
		if ($status) {
			if ($status === true) {
				$status = $this->getConfig()->getStateDefaultStatus($state);
			}
			$this->setStatus($status);
			$history = $this->addStatusHistoryComment($comment, false); // no sense to set $status again
			$history->setIsCustomerNotified($isCustomerNotified); // for backwards compatibility
		}
		return $this;
	}
	
	/**
	* Whether specified state can be set from outside
	* @param $state
	* @return bool
	*/
	public function isStateProtected($state) {
		if (empty($state)) {
			return false;
		}
		return self::STATE_DELETED == $state || self::STATE_ORDER_CLOSED == $state;
	}
	
	/**
	* Retrieve label of transaction status
	*
	* @return string
	*/
	public function getStatusLabel() {
		return $this->getConfig()->getStatusLabel($this->getStatus());
	}
	
	/**
	* Add a comment to transaction
	* Different or default status may be specified
	*
	* @param string $comment
	* @param string $status
	* @return Nostress_Gpwebpay_Transactions_Status_History
	*/
	public function addStatusHistoryComment($comment, $status = false) {
		if (false === $status) {
			$status = $this->getStatus();
		} elseif (true === $status) {
			$status = $this->getConfig()->getStateDefaultStatus($this->getState());
		} else {
			$this->setStatus($status);
		}
		$history = Mage::getModel('nostress_gpwebpay/transactions_status_history')
			->setStatus($status)
			->setComment($comment)
			->setEntityName($this->_historyEntityName);
		$this->addStatusHistory($history);
		return $history;
	}
	
	/**
	* Overrides entity id, which will be saved to comments history status
	*
	* @param string $status
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function setHistoryEntityName( $entityName ) {
		$this->_historyEntityName = $entityName;
		return $this;
	}
	
	/**
	* Place transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function place() {
		Mage::dispatchEvent('nostress_gpwebpay_transactions_place_before', array('transactions'=>$this));
		$this->_placePayment();
		Mage::dispatchEvent('nostress_gpwebpay_transactions_place_after', array('transactions'=>$this));
		return $this;
	}
	
	/**
	* Approve reversal for the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function approveReversal() {
		if ($this->canApproveReversal()) {
			$approveReversedState = self::STATE_APPROVE_REVERSED;
			$currentState = $this->getState();
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "approveReversal",
				"real_order_id" => $this->getRealOrderId()
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('The transaction has not been reversed. The state has not been changed. %s', $request["error"]));
			}
			
			if ($approveReversedState !== $newState && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but the transaction has not been reversed.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been reversed, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been reversed. The state has not been changed.')
				);
			}
			else {
				$comment = "The transaction was reversed";
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('The transaction has been successfully reversed.')
				);
			}
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_approve_reversal_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Deposit the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function deposit() {
		if ($this->canDeposit()) {
			$depositedState = self::STATE_DEPOSIT_BATCH_OPENED;
			$currentState = $this->getState();
			$capture = 0;
			$orderAmount = $this->getOrder()->getGrandTotal();
			list($amount1, $amount2) = explode(".", $orderAmount);
			$orderAmount = $amount1.substr($amount2, 0, 2);
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "deposit",
				"real_order_id" => $this->getRealOrderId(),
				"amount" => $orderAmount
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('The transaction has not been deposited. The state has not been changed. %s', $request["error"]));
			}
			
			if ($depositedState !== $newState && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but the transaction has not been deposited.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been deposited, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been deposited. The state has not been changed.')
				);
			}
			else {
				$invoices = $this->getOrder()->getInvoiceCollection();
				$invoicesArray = array();
				foreach ($invoices as $invoice) {
					if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN && $invoice->getGrandTotal() == $this->getDeposited()) {
						if ($invoice->canCapture()) {
							$invoicesArray[] = $invoice;
						}
					}
				}
				
				if (sizeof($invoicesArray) == 1) {
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->capture()
						->getOrder()
						->save();
					$invoice->save();
					$capture = 1;
					$invoiceId = $invoice->getIncrementId();
				}
				elseif (sizeof($invoicesArray) > 1) {
					$invoice = end($invoicesArray);
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->capture()
						->getOrder()
						->save();
					$invoice->save();
					$capture = 1;
					$invoiceId = $invoice->getIncrementId();
				}
				else {
					$baseGrandTotal = $this->getOrder()->getBaseGrandTotal(); /// TODO: Set deposited amount, not full amount
					$grandTotal = $this->getOrder()->getGrandTotal();
					$invoice = Mage::getModel('sales/order_invoice');
					$invoice->setOrder($this->getOrder())
						->setStoreId($this->getOrder()->getStoreId())
						->setCustomerId($this->getOrder()->getCustomerId())
						->setBillingAddressId($this->getOrder()->getBillingAddressId())
						->setShippingAddressId($this->getOrder()->getShippingAddressId())
						->setTotalQty(0)
						->setBaseGrandTotal($baseGrandTotal)
						->setGrandTotal($grandTotal)
						->setStoreCurrencyCode($this->getOrder()->getStoreCurrencyCode())
						->setOrderCurrencyCode($this->getOrder()->getOrderCurrencyCode())
						->setBaseCurrencyCode($this->getOrder()->getBaseCurrencyCode())
						->setGlobalCurrencyCode($this->getOrder()->getGlobalCurrencyCode());
					$invoice->collectTotals();
					$this->getOrder()->getInvoiceCollection()->addItem($invoice);
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$this->setDeposited($grandTotal)
						->save();
					$invoice->register();
					$transactionSave = Mage::getModel('core/resource_transaction')
						->addObject($invoice)
						->addObject($invoice->getOrder())
						->save();
					if ($invoice->getIncrementId()) {
						$this->getOrder()->addStatusHistoryComment(Mage::helper('nostress_gpwebpay')->__("Invoice for deposit of amount %s was created", $this->getOrder()->getBaseCurrency()->formatTxt($invoice->getGrandTotal())))
							->save();
						$capture = 1;
						$invoiceId = $invoice->getIncrementId();
					}
				}
				
				if ($capture == 0) {
					Mage::getSingleton('adminhtml/session')->addError(
						Mage::helper('nostress_gpwebpay')->__('The Invoice was not updated. Please, check and update the Invoice manually.')
					);
				}
				else {
					Mage::getSingleton('adminhtml/session')->addSuccess(
						Mage::helper('nostress_gpwebpay')->__('The Invoice #%s was updated.', $invoiceId)
					);
				}
				$comment = "The transaction was deposited";
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('The transaction has been successfully deposited.')
				);
			}
			
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_deposit_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Reverse deposit of the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function depositReversal() {
		if ($this->canDepositReversal()) {
			$depositReversedState1 = self::STATE_DEPOSIT_BATCH_OPENED;
			$depositReversedState2 = self::STATE_APPROVED;
			$currentState = $this->getState();
			$memo = 0;
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "depositReversal",
				"real_order_id" => $this->getRealOrderId()
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('Deposit of the Transaction has not been reversed. The state has not been changed. %s', $request["error"]));
			}
			
			if (($depositReversedState1 !== $newState && $depositReversedState2 !== $newState) && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but deposit of the transaction has not been reversed.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('Deposit of the transaction has not been reversed, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('Deposit of the transaction has not been reversed. The state has not been changed.')
				);
			}
			else {
				$payment = $this->getOrder()->getPayment();
				
				$invoices = $this->getOrder()->getInvoiceCollection();
				$invoicesArray = array();
				$invoiceId = "";
				foreach ($invoices as $invoice) {
					if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID && $invoice->getGrandTotal() == $this->getDeposited()) {
						$invoicesArray[] = $invoice;
					}
				}
				
				if (sizeof($invoicesArray) == 1) {
					$invoiceId = $invoice->getIncrementId();
					$memo = 1;
				}
				elseif (sizeof($invoicesArray) > 1) {
					$invoiceId = end($invoicesArray)->getIncrementId();
					$memo = 1;
				}
				
				if ($this->getOrder()->canCreditMemo() == false) {
					$memo = 0;
				}
				
				if ($memo == 1) {
					$creditmemo = Mage::getModel('sales/order_creditmemo');
					$creditmemo->setInvoiceId($invoiceId)
						->setOrder($this->getOrder())
						->setGrandTotal($this->getDeposited())
						->setBaseGrandTotal($this->getDeposited())
						->setState(Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED)
						->save();
					$creditmemoId = $creditmemo->getIncrementId();
					
					$payment->setData('amount_refunded', $payment->getData('amount_refunded')+$creditmemo->getGrandTotal())
						->setData('base_amount_refunded', $payment->getData('base_amount_refunded')+$creditmemo->getBaseGrandTotal())
						->setData('base_amount_refunded_online', $payment->getData('base_amount_refunded_online')+$creditmemo->getBaseGrandTotal());
					$payment->save();
				}
				$this->setDeposited(0)
					->save();
				
				$comment = "Deposit of the Transaction was reversed";
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('Deposit of the transaction has been successfully reversed.')
				);
				if ($memo == 1) {
					$message = Mage::helper('sales')->__('Refunded amount of %s online.', $this->getOrder()->getBaseCurrency()->formatTxt($creditmemo->getBaseGrandTotal()));
					Mage::getSingleton('adminhtml/session')->addSuccess(
						Mage::helper('nostress_gpwebpay')->__('The Credit Memo #%s for invoice #%s was created.', $creditmemoId, $invoiceId)
					);
					$this->getOrder()
						->setBaseTotalOnlineRefunded($this->getOrder()->getBaseTotalOnlineRefunded()+$creditmemo->getBaseGrandTotal())
						->setBaseTotalRefunded($this->getOrder()->getBaseTotalRefunded()+$creditmemo->getBaseGrandTotal())
						->setTotalOnlineRefunded($this->getOrder()->getTotalOnlineRefunded()+$creditmemo->getGrandTotal())
						->setTotalRefunded($this->getOrder()->getTotalRefunded()+$creditmemo->getGrandTotal())
						->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message)
						->save();
				}
				else {
					Mage::getSingleton('adminhtml/session')->addError(
						Mage::helper('nostress_gpwebpay')->__('The Credit Memo was not created. Please, create the Credit Memo manually.')
					);
				}
			}
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_deposit_reversal_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Credit the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function credit() {
		if ($this->canCredit()) {
			$creditedState = self::STATE_CREDITED_BATCH_OPENED;
			$currentState = $this->getState();
			$orderAmount = $this->getOrder()->getGrandTotal();
			list($amount1, $amount2) = explode(".", $orderAmount);
			$orderAmount = $amount1.substr($amount2, 0, 2);
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "credit",
				"real_order_id" => $this->getRealOrderId(),
				"amount" => $orderAmount
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('The transaction has not been credited. The state has not been changed. %s', $request["error"]));
			}
			
			if ($creditedState !== $newState && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but the transaction has not been credited.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been credited, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been credited. The state has not been changed.')
				);
			}
			else {
				$payment = $this->getOrder()->getPayment();
				
				$invoices = $this->getOrder()->getInvoiceCollection();
				$invoicesArray = array();
				$invoiceId = "";
				foreach ($invoices as $invoice) {
					if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID && $invoice->getGrandTotal() == $this->getDeposited()) {
						$invoicesArray[] = $invoice;
					}
				}
				
				if (sizeof($invoicesArray) == 1) {
					$invoiceId = $invoice->getIncrementId();
					$memo = 1;
				}
				elseif (sizeof($invoicesArray) > 1) {
					$invoiceId = end($invoicesArray)->getIncrementId();
					$memo = 1;
				}
				
				if ($this->getOrder()->canCreditMemo() == false) {
					$memo = 0;
				}
				
				if ($memo == 1) {
					$creditmemo = Mage::getModel('sales/order_creditmemo');
					$creditmemo->setInvoiceId($invoiceId)
						->setOrder($this->getOrder())
						->setStoreId($this->getOrder()->getStoreId())
						->setCustomerId($this->getOrder()->getCustomerId())
						->setBillingAddressId($this->getOrder()->getBillingAddressId())
						->setShippingAddressId($this->getOrder()->getShippingAddressId())
						->setGrandTotal($this->getDeposited())
						->setBaseGrandTotal($this->getDeposited())
						->setState(Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED)
						->save();
					$creditmemoId = $creditmemo->getIncrementId();
					
					$payment->setData('amount_refunded', $payment->getData('amount_refunded')+$creditmemo->getGrandTotal())
						->setData('base_amount_refunded', $payment->getData('base_amount_refunded')+$creditmemo->getBaseGrandTotal())
						->setData('base_amount_refunded_online', $payment->getData('base_amount_refunded_online')+$creditmemo->getBaseGrandTotal());
					$payment->save();
				}
				$creditId = (int)$this->getData('credit_id')+1;
				$this->setData('credited', $this->getDeposited())
					->setData('credit_id', $creditId)
					->save();
				
				if ($memo == 1) {
					$message = Mage::helper('sales')->__('Refunded amount of %s online.', $this->getOrder()->getBaseCurrency()->formatTxt($creditmemo->getBaseGrandTotal()));
					Mage::getSingleton('adminhtml/session')->addSuccess(
						Mage::helper('nostress_gpwebpay')->__('The Credit Memo #%s for invoice #%s was created.', $creditmemoId, $invoiceId)
					);
					$this->getOrder()
						->setBaseTotalOnlineRefunded($this->getOrder()->getBaseTotalOnlineRefunded()+$creditmemo->getBaseGrandTotal())
						->setBaseTotalRefunded($this->getOrder()->getBaseTotalRefunded()+$creditmemo->getBaseGrandTotal())
						->setTotalOnlineRefunded($this->getOrder()->getTotalOnlineRefunded()+$creditmemo->getGrandTotal())
						->setTotalRefunded($this->getOrder()->getTotalRefunded()+$creditmemo->getGrandTotal())
						->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message)
						->save();
				}
				else {
					Mage::getSingleton('adminhtml/session')->addError(
						Mage::helper('nostress_gpwebpay')->__('The Credit Memo was not created. Please, create the Credit Memo manually.')
					);
				}
				
				$comment = Mage::helper('nostress_gpwebpay')->__('The transaction was credited with amount of %s', $this->getOrder()->getBaseCurrency()->formatTxt($this->getDeposited()));
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('The transaction has been successfully credited with amount of %s.', $this->getOrder()->getBaseCurrency()->formatTxt($this->getDeposited()))
				);
			}
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_credit_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Reverse credit for the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function creditReversal() {
		if ($this->canCreditReversal()) {
			$creditReversalState1 = self::STATE_CREDITED_BATCH_CLOSED;
			$creditReversalState2 = self::STATE_DEPOSIT_BATCH_CLOSED;
			$currentState = $this->getState();
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "creditReversal",
				"real_order_id" => $this->getRealOrderId(),
				"credit_number" => (int)$this->getData('credit_id')
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('Credit for the Transaction has not been reversed. The state has not been changed. %s', $request["error"]));
			}
			
			if (($creditReversalState1 !== $newState && $creditReversalState2 !== $newState) && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but credit for the transaction has not been reversed.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('Credit for the transaction has not been reversed, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('Credit for the transaction has not been reversed. The state has not been changed.')
				);
			}
			else {
				$invoices = $this->getOrder()->getInvoiceCollection();
				$invoicesArray = array();
				foreach ($invoices as $invoice) {
					if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN && $invoice->getGrandTotal() == $this->getDeposited()) {
						if ($invoice->canCapture()) {
							$invoicesArray[] = $invoice;
						}
					}
				}
				
				if (sizeof($invoicesArray) == 1) {
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->capture()
						->getOrder()
						->save();
					$invoice->save();
					$capture = 1;
					$invoiceId = $invoice->getIncrementId();
				}
				elseif (sizeof($invoicesArray) > 1) {
					$invoice = end($invoicesArray);
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->capture()
						->getOrder()
						->save();
					$invoice->save();
					$capture = 1;
					$invoiceId = $invoice->getIncrementId();
				}
				else {
					$baseGrandTotal = $this->getCredited(); /// TODO: Set deposited amount, not full amount
					$grandTotal = $this->getCredited();
					$invoice = Mage::getModel('sales/order_invoice');
					$invoice->setOrder($this->getOrder())
						->setStoreId($this->getOrder()->getStoreId())
						->setCustomerId($this->getOrder()->getCustomerId())
						->setBillingAddressId($this->getOrder()->getBillingAddressId())
						->setShippingAddressId($this->getOrder()->getShippingAddressId())
						->setTotalQty(0)
						->setBaseGrandTotal($baseGrandTotal)
						->setGrandTotal($grandTotal)
						->setStoreCurrencyCode($this->getOrder()->getStoreCurrencyCode())
						->setOrderCurrencyCode($this->getOrder()->getOrderCurrencyCode())
						->setBaseCurrencyCode($this->getOrder()->getBaseCurrencyCode())
						->setGlobalCurrencyCode($this->getOrder()->getGlobalCurrencyCode());
					$invoice->collectTotals();
					$this->getOrder()->getInvoiceCollection()->addItem($invoice);
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->register();
					$transactionSave = Mage::getModel('core/resource_transaction')
						->addObject($invoice)
						->addObject($invoice->getOrder())
						->save();
					if ($invoice->getIncrementId()) {
						$this->getOrder()->addStatusHistoryComment(Mage::helper('nostress_gpwebpay')->__("Invoice for credit reversal of amount %s was created", $this->getOrder()->getBaseCurrency()->formatTxt($invoice->getGrandTotal())))
							->save();
						$capture = 1;
						$invoiceId = $invoice->getIncrementId();
					}
				}
				
				$this->setCredited(0)
					->save();
				
				if ($capture == 0) {
					Mage::getSingleton('adminhtml/session')->addError(
						Mage::helper('nostress_gpwebpay')->__('The Invoice was not created. Please, create the Invoice manually.')
					);
				}
				else {
					Mage::getSingleton('adminhtml/session')->addSuccess(
						Mage::helper('nostress_gpwebpay')->__('The Invoice #%s was created.', $invoiceId)
					);
				}
				
				$comment = "Credit for the transaction was reversed";
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('Credit for the transaction has been successfully reversed.')
				);
			}
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_credit_reversal_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Close the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function orderClose() {
		if ($this->canOrderClose()) {
			$closedState = self::STATE_ORDER_CLOSED;
			$currentState = $this->getState();
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "orderClose",
				"real_order_id" => $this->getRealOrderId()
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('The transaction has not been closed. The state has not been changed. %s', $request["error"]));
			}
			
			if ($closedState !== $newState && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but the transaction has not been closed.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been closed, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been closed. The state has not been changed.')
				);
			}
			else {
				$comment = "The transaction was closed";
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('The transaction has been successfully closed.')
				);
			}
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_order_close_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Delete the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function delete() {
		if ($this->canDelete()) {
			$deletedState = self::STATE_DELETED;
			$currentState = $this->getState();
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "delete",
				"real_order_id" => $this->getRealOrderId()
			));
			$newState = $this->getNewState();
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('The transaction has not been deleted. The state has not been changed. %s', $request["error"]));
			}
			
			if ($deletedState !== $newState && $newState !== $currentState) {
				$comment = Mage::helper('nostress_gpwebpay')->__('The state has been changed, but the transaction has not been deleted.');
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been deleted, but the state has changed.')
				);
			}
			else if ($newState === $currentState) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('nostress_gpwebpay')->__('The transaction has not been deleted. The state has not been changed.')
				);
			}
			else {
				$comment = "The transaction was deleted";
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('The transaction has been successfully deleted.')
				);
			}
			if ($newState !== $currentState) {
				$this->_setState($newState, true, $comment);
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_delete_after', array('transaction' => $this));
		}
		
		return $this;
	}
	
	/**
	* Query state of the transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function queryOrderState($inform = true, $batch = false) {
		if ($this->canQueryOrderState()) {
			$currentState = $this->getState();
			$newState = $this->getNewState();
			
			if ($currentState === $newState) {
				if ($inform === true) {
					Mage::getSingleton('adminhtml/session')->addNotice(
						Mage::helper('nostress_gpwebpay')->__('State of the transaction (#%s) has not been updated. No new state found.', $this->getId())
					);
				}
				$this->_beforeSave();
			}
			else {
				if ($batch === true) {
					$comment = Mage::helper('nostress_gpwebpay')->__('State updated after batch close');
				}
				else {
					$comment = Mage::helper('nostress_gpwebpay')->__('State updated automatically');
				}
				$this->_setState($newState, true, $comment);
				if ($inform === true) {
					Mage::getSingleton('adminhtml/session')->addSuccess(
						Mage::helper('nostress_gpwebpay')->__('State of the transaction (#%s) has been successfully updated.', $this->getId())
					);
				}
			}
			
			Mage::dispatchEvent('transactions_query_order_state_after', array('transaction' => $this));
			$this->save();
		}
		
		return $this;
	}
	
	public function batchClose() {
		if ($this->canBatchClose()) {
			$request = Mage::getModel('nostress_gpwebpay/abstract')->soapAction(array(
				"action" => "batchClose"
			));
			
			if (!empty($request["error"])) {
				Mage::throwException(Mage::helper('nostress_gpwebpay')->__('Transactions have not been batch closed. %s', $request["error"]));
			}
			else {
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('nostress_gpwebpay')->__('Transaction have been successfully batch closed.')
				);
			}
			
			$this->_transactions = Mage::getResourceModel('nostress_gpwebpay/transactions_collection')
				->setOrder('created_at', 'desc')
				->setOrder('entity_id', 'desc');
			
			foreach ($this->_transactions as $transaction) {
				if ($transaction->isCreditedOpen() || $transaction->isDepositedOpen()) {
					$transaction->queryOrderState(false, true);
				}
			}
			
			if (!empty($request["request_id"])) {
				$this->_requestId = $request["request_id"];
			}
			
			Mage::dispatchEvent('transactions_batch_close_after', array('transaction' => $this));
		}
		
		return $this->_transactions;
	}
	
//@array_walk(debug_backtrace(),create_function('$a,$b', 'echo "<br /><b>". basename( $a[\'file\'] ). "</b> &nbsp; <font color=\"red\">{$a[\'line\']}</font> &nbsp; <font color=\"green\">{$a[\'function\']} ()</font> &nbsp; -- ". dirname( $a[\'file\'] ). "/";'));

/*********************** STATUSES ***************************/
	
	/**
	* Return transaction status history collection
	*
	* @return Nostress_Gpwebpay_Model_Entity_Transactions_Status_History_Collection
	*/
	public function getStatusHistoryCollection($reload=false) {
		if (is_null($this->_statusHistory) || $reload) {
			$this->_statusHistory = Mage::getResourceModel('nostress_gpwebpay/transactions_status_history_collection')
				->setTransactionFilter($this)
				->setOrder('created_at', 'desc')
				->setOrder('entity_id', 'desc');
			
			if ($this->getId()) {
				foreach ($this->_statusHistory as $status) {
					$status->setTransaction($this);
				}
			}
		}
		return $this->_statusHistory;
	}
	
	/**
	* Return collection of transaction status history items.
	*
	* @return array
	*/
	public function getAllStatusHistory() {
		$history = array();
		foreach ($this->getStatusHistoryCollection() as $status) {
			if (!$status->isDeleted()) {
				$history[] =  $status;
			}
		}
		return $history;
	}
	
	/**
	* Return collection of visible on frontend transaction status history items.
	*
	* @return array
	*/
	public function getVisibleStatusHistory() {
		$history = array();
		foreach ($this->getStatusHistoryCollection() as $status) {
			if (!$status->isDeleted() && $status->getComment() && $status->getIsVisibleOnFront()) {
				$history[] =  $status;
			}
		}
		return $history;
	}
	
	public function getStatusHistoryById($statusId) {
		foreach ($this->getStatusHistoryCollection() as $status) {
			if ($status->getId()==$statusId) {
				return $status;
			}
		}
		return false;
	}
	
	/**
	* Set the transaction status history object and the transaction object to each other
	* Adds the object to the status history collection, which is automatically saved when the transaction is saved.
	* Or the history record can be saved standalone after this.
	*
	* @param Nostress_Gpwebpay_Model_Transactions_Status_History $status
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function addStatusHistory(Nostress_Gpwebpay_Model_Transactions_Status_History $history) {
		$history->setTransaction($this);
		$this->setStatus($history->getStatus());
		if (!$history->getId()) {
			$this->getStatusHistoryCollection()->addItem($history);
		}
		return $this;
	}
	
	/**
	*
	* @return int
	*/
	public function getOrderId() {
		$id = $this->getData('order_id');
		if (is_null($id)) { /// TODO: Throw error, this transaction does not have order for it
			//$id = $this->getIncrementId();
		}
		return $id;
	}
    
	/**
	*
	* @return string
	*/
	public function getRealTransactionId() {
		$id = $this->getData('entity_id');
		if (is_null($id)) {
			$id = $this->getIncrementId();
		}
		return $id;
	}
	
	/**
	*
	* @return string
	*/
	public function getRealOrderId() {
		$id = $this->getData('real_order_id');
		if (is_null($id)) { /// TODO: Throw error, this transaction does not have order for it
			$id = $this->getIncrementId();
		}
		return $id;
	}
	
	public function getData($key='', $index=null) {
		return parent::getData($key, $index);
	}
	
	/**
	* Retrieve array of related objects
	*
	* Used for transaction saving
	*
	* @return array
	*/
	public function getRelatedObjects() {
		return $this->_relatedObjects;
	}
	
	/**
	* Add New object to related array
	*
	* @param   Mage_Core_Model_Abstract $object
	* @return  Nostress_Gpwebpay_Model_Transactions
	*/
	public function addRelatedObject(Mage_Core_Model_Abstract $object) {
		$this->_relatedObjects[] = $object;
		return $this;
	}
	
	/**
	* Get formated transaction created date in store timezone
	*
	* @param   string $format date format type (short|medium|long|full)
	* @return  string
	*/
	public function getCreatedAtFormated($format) {
		return Mage::helper('core')->formatDate($this->getCreatedAtStoreDate(), $format, true);
	}
	
	/**
	* Processing object before save data
	*
	* @return Mage_Core_Model_Abstract
	*/
	protected function _beforeSave() {
		parent::_beforeSave();
		//$this->setData('protect_code', substr(md5(uniqid(mt_rand(), true) . ':' . microtime(true)), 5, 6));
		return $this;
	}
	
	/**
	* Save transaction related objects
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	protected function _afterSave() {
		if (null !== $this->_statusHistory) {
			$this->_statusHistory->save();
		}
		foreach ($this->getRelatedObjects() as $object) {
			$object->save();
		}
		return parent::_afterSave();
	}
	
	/**
	* Resets all data in object
	* so after another load it will be complete new object
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function reset() {
		$this->unsetData();
		$this->_actionFlag = array();
		$this->_statusHistory = null;
		$this->_relatedObjects = array();
		$this->_requestId = "";
		
		return $this;
	}
	
	/**
	* Check whether transaction is approved
	*
	* @return bool
	*/
	public function isApproved() {
		return ($this->getState() === self::STATE_APPROVED);
	}
	
	/**
	* Check whether transaction is approve reversed
	*
	* @return bool
	*/
	public function isApproveReversed() {
		return ($this->getState() === self::STATE_APPROVE_REVERSED);
	}
	
	/**
	* Check whether transaction is deposited
	*
	* @return bool
	*/
	public function isDeposited() {
		return ($this->getState() === self::STATE_DEPOSIT_BATCH_OPENED || $this->getState() === self::STATE_DEPOSIT_BATCH_CLOSED);
	}
	
	/**
	* Check whether transaction is deposited (Opened)
	*
	* @return bool
	*/
	public function isDepositedOpen() {
		return ($this->getState() === self::STATE_DEPOSIT_BATCH_OPENED);
	}
	
	/**
	* Check whether transaction is deposited (Closed)
	*
	* @return bool
	*/
	public function isDepositedClosed() {
		return ($this->getState() === self::STATE_DEPOSIT_BATCH_CLOSED);
	}
	
	/**
	* Check whether deposit of the transaction is reversed
	*
	* @return bool
	*/
	public function isDepositReversed() {
		return ($this->getState() === self::STATE_APPROVED);
	}
	
	/**
	* Check whether the transaction is credited
	*
	* @return bool
	*/
	public function isCredited() {
		return ($this->getState() === self::STATE_CREDITED_BATCH_CLOSED || $this->getState() === self::STATE_CREDITED_BATCH_OPENED);
	}
	
	/**
	* Check whether the transaction is credited (Opened)
	*
	* @return bool
	*/
	public function isCreditedOpen() {
		return ($this->getState() === self::STATE_CREDITED_BATCH_OPENED);
	}
	
	/**
	* Check whether the transaction is credited (Closed)
	*
	* @return bool
	*/
	public function isCreditedClosed() {
		return ($this->getState() === self::STATE_CREDITED_BATCH_CLOSED);
	}
	
	/**
	* Check whether the transaction is credit reversed
	*
	* @return bool
	*/
	public function isCreditReversed() {
		return ($this->getState() === self::STATE_CREDITED_BATCH_OPENED);
	}
	
	/**
	* Protect transaction delete from not admin scope
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	protected function _beforeDelete() {
		$this->_protectFromNonAdmin();
		return parent::_beforeDelete();
	}
}
