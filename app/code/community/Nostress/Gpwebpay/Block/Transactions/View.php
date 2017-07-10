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
 * Transaction view
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct() {
		$this->_objectId    = 'transaction_id';
		$this->_controller  = 'transactions';
		$this->_blockGroup  = 'nostress_gpwebpay';
		$this->_mode        = 'view';
		
		parent::__construct();
		
		$this->_removeButton('delete');
		$this->_removeButton('reset');
		$this->_removeButton('save');
		$this->setId('nostress_gpwebpay_transactions_view');
		$transaction = $this->getTransaction();
		
		if ($this->_isAllowedAction('approve_reversal') && $transaction->canApproveReversal()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to approve reversal for this transaction?');
			$this->_addButton('transaction_approve_reversal', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Approve reversal'),
				'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getApproveReversalUrl() . '\')',
			));
		}
		
		if ($this->_isAllowedAction('deposit') && $transaction->canDeposit()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to deposit this transaction?');
			$this->_addButton('transaction_deposit', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Deposit'),
				'onclick'   => "confirmSetLocation('{$message}', '{$this->getDepositUrl()}')",
			));
		}
		
		if ($this->_isAllowedAction('depositReversal') && $transaction->canDepositReversal()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to reverse deposit for this transaction?');
			$this->_addButton('transaction_deposit_reversal', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Deposit reversal'),
				'onclick'   => "confirmSetLocation('{$message}', '{$this->getDepositReversalUrl()}')",
			));
		}
		
		if ($this->_isAllowedAction('credit') && $transaction->canCredit()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to credit this transaction?');
			$this->_addButton('transaction_credit', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Credit'),
				'onclick'   => "confirmSetLocation('{$message}', '{$this->getCreditUrl()}')",
			));
		}
		
		if ($this->_isAllowedAction('creditReversal') && $transaction->canCreditReversal()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to reverse credit for this transaction?');
			$this->_addButton('transaction_credit_reversal', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Credit reversal'),
				'onclick'   => "confirmSetLocation('{$message}', '{$this->getCreditReversalUrl()}')",
			));
		}
		
		if ($this->_isAllowedAction('orderClose') && $transaction->canOrderClose()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to close the transaction?');
			$this->_addButton('transaction_order_close', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Close transaction'),
				'onclick'   => "confirmSetLocation('{$message}', '{$this->getOrderCloseUrl()}')",
			));
		}
		
		if ($this->_isAllowedAction('delete') && $transaction->canDelete()) {
			$message = Mage::helper('nostress_gpwebpay')->__('Are you sure you want to delete the transaction?');
			$this->_addButton('transaction_delete', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Delete transaction'),
				'onclick'   => 'deleteConfirm(\''.$message.'\', \''.$this->getDeleteUrl().'\')',
			));
		}
		
		if ($this->_isAllowedAction('queryOrderState') && $transaction->canQueryOrderState()) {
			$this->_addButton('transaction_query_order_state', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Query state'),
				'onclick'   => 'setLocation(\''.$this->getQueryOrderStateUrl().'\')',
				//'class'     => 'go'
			));
		}
	}
	
	/**
	* Retrieve transaction model object
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getTransaction() {
		return Mage::registry('nostress_gpwebpay_transactions');
	}
	
	/**
	* Retrieve Transaction Identifier
	*
	* @return int
	*/
	public function getTransactionId() {
		return $this->getTransaction()->getId();
	}
	
	public function getHeaderText() {
		if ($_extTransactionId = $this->getTransaction()->getExtTransactionId()) {
			$_extTransactionId = '[' . $_extTransactionId . '] ';
		}
		else {
			$_extTransactionId = '';
		}
		return Mage::helper('nostress_gpwebpay')->__('Transaction # %s %s | %s', $this->getTransaction()->getRealTransactionId(), $_extTransactionId, $this->formatDate($this->getTransaction()->getCreatedAtDate(), 'medium', true));
	}
	
	public function getUrl($params='', $params2=array()) {
		$params2['transaction_id'] = $this->getTransactionId();
		return parent::getUrl($params, $params2);
	}
	
	public function getApproveReversalUrl() {
		return $this->getUrl('*/*/approveReversal');
	}
	
	public function getDepositUrl() {
		return $this->getUrl('*/*/deposit');
	}
	
	public function getDepositReversalUrl() {
		return $this->getUrl('*/*/depositReversal');
	}
	
	public function getCreditUrl() {
		return $this->getUrl('*/*/credit');
	}
	
	public function getCreditReversalUrl() {
		return $this->getUrl('*/*/creditReversal');
	}
	
	public function getOrderCloseUrl() {
		return $this->getUrl('*/*/orderClose');
	}
	
	public function getDeleteUrl() {
		return $this->getUrl('*/*/delete');
	}
	
	public function getQueryOrderStateUrl() {
		return $this->getUrl('*/*/queryOrderState');
	}
	
	protected function _isAllowedAction($action) {
		return Mage::getSingleton('admin/session')->isAllowed('nostress_gpwebpay/transactions/actions/'.$action);
	}
	
	/**
	* Return back url for view grid
	*
	* @return string
	*/
	public function getBackUrl() {
		if ($this->getTransaction()->getBackUrl()) {
			return $this->getTransaction()->getBackUrl();
		}
		
		return $this->getUrl('*/*/');
	}
}
