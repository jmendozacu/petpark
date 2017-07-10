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
 * Transaction history tab
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_View_Tab_History extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	protected function _construct() {
		parent::_construct();
		$this->setTemplate('nostress/gpwebpay/transactions/view/tab/history.phtml');
	}
	
	/**
	* Retrieve transaction model instance
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getTransaction() {
		return Mage::registry('current_transaction');
	}
	
	/**
	* Compose and get transaction full history.
	* Consists of the status history comments as well as of invoices, shipments and creditmemos creations
	* @return array
	*/
	public function getFullHistory() {
		$transaction = $this->getTransaction();
		
		$history = array();
		foreach ($transaction->getAllStatusHistory() as $transactionComment){
			$history[$transactionComment->getEntityId()] = $this->_prepareHistoryItem(
				$transactionComment->getStatusLabel(),
				$transactionComment->getIsCustomerNotified(),
				$transactionComment->getCreatedAtDate(),
				$transactionComment->getComment()
			);
		}
		
		krsort($history);
		return $history;
	}
	
	/**
	* Status history date/datetime getter
	* @param array $item
	* @return string
	*/
	public function getItemCreatedAt(array $item, $dateType = 'date', $format = 'medium') {
		if (!isset($item['created_at'])) {
			return '';
		}
		if ('date' === $dateType) {
			return $this->helper('core')->formatDate($item['created_at'], $format);
		}
		return $this->helper('core')->formatTime($item['created_at'], $format);
	}
	
	/**
	* Status history item title getter
	* @param array $item
	* @return string
	*/
	public function getItemTitle(array $item) {
		return (isset($item['title']) ? $this->escapeHtml($item['title']) : '');
	}
	
	/**
	* Check whether status history comment is with customer notification
	* @param array $item
	* @return bool
	*/
	public function isItemNotified(array $item, $isSimpleCheck = true) {
		if ($isSimpleCheck) {
			return !empty($item['notified']);
		}
		return isset($item['notified']) && false !== $item['notified'];
	}
	
	/**
	* Status history item comment getter
	* @param array $item
	* @return string
	*/
	public function getItemComment(array $item) {
		$allowedTags = array('b','br','strong','i','u');
		return (isset($item['comment']) ? $this->escapeHtml($item['comment'], $allowedTags) : '');
	}
	
	/**
	* Map history items as array
	* @param string $label
	* @param bool $notified
	* @param Zend_Date $created
	* @param string $comment
	*/
	protected function _prepareHistoryItem($label, $notified, $created, $comment = '') {
		return array(
			'title'      => $label,
			'notified'   => $notified,
			'comment'    => $comment,
			'created_at' => $created
		);
	}
	
	/**
	* ######################## TAB settings #################################
	*/
	public function getTabLabel() {
		return Mage::helper('nostress_gpwebpay')->__('Transaction History');
	}
	
	public function getTabTitle() {
		return Mage::helper('nostress_gpwebpay')->__('Transaction History');
	}
	
	public function getTabClass() {
		return 'ajax only';
	}
	
	public function getClass() {
		return $this->getTabClass();
	}
	
	public function getTabUrl() {
		return $this->getUrl('*/*/history', array('_current' => true));
	}
	
	public function canShowTab() {
		return true;
	}
	
	public function isHidden() {
		return false;
	}
	
	/**
	* Customer Notification Applicable check method
	*
	* @param array $history
	* @return boolean
	*/
	public function isCustomerNotificationNotApplicable($historyItem) {
		return $historyItem['notified'] == Nostress_Gpwebpay_Model_Transactions_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE;
	}
}