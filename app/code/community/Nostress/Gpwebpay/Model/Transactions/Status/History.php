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
 * Transaction status history comments
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Transactions_Status_History extends Nostress_Gpwebpay_Model_TransactionsAbstract
{
	const CUSTOMER_NOTIFICATION_NOT_APPLICABLE = 2;
	
	/**
	* Transaction instance
	*
	* @var Nostress_Gpwebpay_Model_Transactions
	*/
	protected $_transaction;
	
	protected $_eventPrefix = 'nostress_gpwebpay_transactions_status_history';
	protected $_eventObject = 'status_history';
	
	/**
	* Initialize resource model
	*/
	protected function _construct() {
		$this->_init('nostress_gpwebpay/transactions_status_history');
	}
	
	/**
	* Set transaction object and grab some metadata from it
	*
	* @param   Nostress_Gpwebpay_Model_Transactions $transaction
	* @return  Nostress_Gpwebpay_Model_Transactions_Status_History
	*/
	public function setTransaction(Nostress_Gpwebpay_Model_Transactions $transaction) {
		$this->_transaction = $transaction;
		$this->setStoreId($transaction->getOrder()->getStoreId());
		return $this;
	}
	
	/**
	* Notification flag
	*
	* @param  mixed $flag OPTIONAL (notification is not applicable by default)
	* @return Nostress_Gpwebpay_Model_Transactions_Status_History
	*/
	public function setIsCustomerNotified($flag = null) {
		if (is_null($flag)) {
			$flag = self::CUSTOMER_NOTIFICATION_NOT_APPLICABLE;
		}
		
		return $this->setData('is_customer_notified', $flag);
	}
	
	/**
	* Customer Notification Applicable check method
	*
	* @return boolean
	*/
	public function isCustomerNotificationNotApplicable() {
		return $this->getIsCustomerNotified() == self::CUSTOMER_NOTIFICATION_NOT_APPLICABLE;
	}
	
	/**
	* Retrieve transaction instance
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getTransaction() {
		return $this->_transaction;
	}
	
	/**
	* Retrieve order instance
	*
	* @return Mage_Sales_Model_Order
	*/
	public function getOrder() {
		return $this->getTransaction()->getOrder();
	}
	
	/**
	* Retrieve status label
	*
	* @return string
	*/
	public function getStatusLabel() {
		if($this->getTransaction()) {
			return $this->getTransaction()->getConfig()->getStatusLabel($this->getStatus());
		}
	}
	
	/**
	* Get store object
	*
	* @return unknown
	*/
	public function getStore() {
		if ($this->getOrder()) {
			return $this->getOrder()->getStore();
		}
		return Mage::app()->getStore();
	}
	
	/**
	* Set transaction again if required
	*
	* @return Nostress_Gpwebpay_Model_Transactions_Status_History
	*/
	protected function _beforeSave() {
		parent::_beforeSave();
		
		if (!$this->getParentId() && $this->getTransaction()) {
			$this->setParentId($this->getTransaction()->getId());
		}
		
		return $this;
	}
}