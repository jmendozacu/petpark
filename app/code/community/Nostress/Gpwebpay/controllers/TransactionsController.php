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
 * Transactions controller
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_TransactionsController extends Mage_Adminhtml_Controller_Action
{
	
	protected function _construct() {
		$this->setUsedModuleName('Nostress_Gpwebpay');
	}
	
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('sales/nostress_gpwebpay/transactions')
			->_addBreadcrumb($this->__('Sales'), $this->__('Sales'))
			->_addBreadcrumb(Mage::helper('nostress_gpwebpay')->__('GPWebPay Transactions'), Mage::helper('nostress_gpwebpay')->__('GPWebPay Transactions'));
		return $this;
	}
	
	/**
	* Initialize transactions model instance
	*
	* @return Nostress_Gpwebpay_Model_Transactions || false
	*/
	protected function _initTransaction() {
		$id = $this->getRequest()->getParam('transaction_id');
		$transaction = Mage::getModel('nostress_gpwebpay/transactions')->load($id);

		if (!$transaction->getId()) {
			$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('This transaction no longer exists.'));
			$this->_redirect('*/*/');
			$this->setFlag('', self::FLAG_NO_DISPATCH, true);
			return false;
		}
		Mage::register('nostress_gpwebpay_transactions', $transaction);
		Mage::register('nostress_gpwebpay_transaction', $transaction);
		Mage::register('current_transaction', $transaction);
		return $transaction;
	}
	
	public function indexAction() {
		$this->_title($this->__('Sales'))->_title(Mage::helper('nostress_gpwebpay')->__('GPWebPay Transactions'));
		
		$this->_initAction()
			->renderLayout();
	}
	
	public function gridAction() {
		$this->loadLayout(false);
		$this->renderLayout();
	}
	
	public function viewAction() {
		$this->_title($this->__('Sales'))->_title(Mage::helper('nostress_gpwebpay')->__('GPWebPay Transactions'));

		if ($transaction = $this->_initTransaction()) {
			$this->_initAction();

			$this->_title(sprintf("#%s", $transaction->getRealTransactionId()));

			$this->renderLayout();
		}
	}
	
	/**
	* Generate order history for ajax request
	*/
	public function historyAction() {
		$this->_initTransaction();
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('nostress_gpwebpay/transactions_view_tab_history')->toHtml()
		);
	}
	
	/**
	* Approve reversal for the transaction
	*/
	public function approveReversalAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->approveReversal()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('The transaction has not been reversed. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Deposit the transaction
	*/
	public function depositAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->deposit()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('The transaction has not been deposited. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Reverse deposit of the transaction
	*/
	public function depositReversalAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->depositReversal()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('Deposit of the transaction has not been reversed. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Credit the transaction
	*/
	public function creditAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->credit()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('The transaction has not been credited. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Reverse credit for the transaction
	*/
	public function creditReversalAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->creditReversal()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('Credit for this transaction has not been reversed. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Close the transaction
	*/
	public function orderCloseAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->orderClose()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('The transaction has not been closed. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Delete the transaction
	*/
	public function deleteAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->delete()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('The transaction has not been deleted. Technical error occured, please, try again.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Query state of the transaction
	*/
	public function queryOrderStateAction() {
		if ($transaction = $this->_initTransaction()) {
			try {
				$transaction->queryOrderState()
					->save();
			}
			catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
			catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('State of the transaction has not been updated.'));
				Mage::logException($e);
			}
			$this->_redirect('*/*/view', array('transaction_id' => $transaction->getId()));
		}
	}
	
	/**
	* Batch close for all transactions
	*/
	public function batchCloseAction() {
		$transactions = Mage::getModel("nostress_gpwebpay/transactions");
		
		try {
			$transactions->batchClose();
		}
		catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		}
		catch (Exception $e) {
			$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('Batch of transactions has not been closed.'));
			Mage::logException($e);
		}
		$this->_redirect('*/*/');
	}
	
	/**
	* Query order state for all transactions
	*/
	public function allQueryOrderStateAction() {
		/*$transactionIds = $this->getRequest()->getPost('transaction_ids', array());
		$countQueryOrderState = 0;
		$countNonQueryOrderState = 0;
		foreach ($transactionIds as $transactionId) {
			$transaction = Mage::getModel('nostress_gpwebpay/transactions')->load($transactionId);
			if ($transaction->canQueryOrderState()) {
				$transaction->queryOrderState()
					->save();
				$countQueryOrderState++;
			}
			else {
				$countNonQueryOrderState++;
			}
		}
		if ($countNonQueryOrderState) {
			if ($countQueryOrderState) {
				$this->_getSession()->addError($this->__('%s transaction(s) cannot be queried', $countNonQueryOrderState));
			}
			else {
				$this->_getSession()->addError($this->__('The transaction(s) cannot be queried'));
			}
		}
		if ($countQueryOrderState) {
			$this->_getSession()->addSuccess($this->__('%s transaction(s) have been queried.', $countQueryOrderState));
		}*/
		$this->_redirect('*/*/');
	}
	
	/**
	* Query order state for selected transactions
	*/
	public function massQueryOrderStateAction() {
		$transactionIds = $this->getRequest()->getPost('transaction_ids', array());
		$countQueryOrderState = 0;
		$countNonQueryOrderState = 0;
		foreach ($transactionIds as $transactionId) {
			$transaction = Mage::getModel('nostress_gpwebpay/transactions')->load($transactionId);
			if ($transaction->canQueryOrderState()) {
				$transaction->queryOrderState()
					->save();
				$countQueryOrderState++;
			}
			else {
				$countNonQueryOrderState++;
			}
		}
		if ($countNonQueryOrderState) {
			if ($countQueryOrderState) {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('%s transaction(s) cannot be queried', $countNonQueryOrderState));
			}
			else {
				$this->_getSession()->addError(Mage::helper('nostress_gpwebpay')->__('The transaction(s) cannot be queried'));
			}
		}
		if ($countQueryOrderState) {
			$this->_getSession()->addSuccess(Mage::helper('nostress_gpwebpay')->__('%s transaction(s) have been queried.', $countQueryOrderState));
		}
		$this->_redirect('*/*/');
	}
	
	/**
	* Acl check for admin
	*
	* @return bool
	*/
	/*protected function _isAllowed() {
		$action = strtolower($this->getRequest()->getActionName());
		switch ($action) {
			case 'hold':
				$aclResource = 'sales/order/actions/hold';
			break;
			case 'unhold':
				$aclResource = 'sales/order/actions/unhold';
			break;
			case 'email':
				$aclResource = 'sales/order/actions/email';
			break;
			case 'cancel':
				$aclResource = 'sales/order/actions/cancel';
			break;
			case 'view':
				$aclResource = 'sales/order/actions/view';
			break;
			case 'addcomment':
				$aclResource = 'sales/order/actions/comment';
			break;
			case 'creditmemos':
				$aclResource = 'sales/order/actions/creditmemo';
			break;
			case 'reviewpayment':
				$aclResource = 'sales/order/actions/review_payment';
			break;
			default:
				$aclResource = 'sales/order';
			break;
		}
		return Mage::getSingleton('admin/session')->isAllowed($aclResource);
	}*/
}