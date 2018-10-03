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
 * Transaction history block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_View_History extends Mage_Adminhtml_Block_Template
{
	protected function _prepareLayout() {
		/*$onclick = "submitAndReloadArea($('transaction_history_block').parentNode, '".$this->getSubmitUrl()."')";
		$button = $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label'   => Mage::helper('nostress_gpwebpay')->__('Submit Comment'),
				'class'   => 'save',
				'onclick' => $onclick
			));
		$this->setChild('submit_button', $button);*/
		return parent::_prepareLayout();
	}
	
	public function getStatuses() {
		$state = $this->getTransaction()->getState();
		$statuses = $this->getTransaction()->getConfig()->getStateStatuses($state);
		return $statuses;
	}
	
	/*public function canSendCommentEmail() {
		return Mage::helper('nostress_gpwebpay')->canSendOrderCommentEmail($this->getTransaction()->getStore()->getId());
	}*/
	
	/**
	* Retrieve transaction model
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getTransaction() {
		return Mage::registry('nostress_gpwebpay_transactions');
	}
	
	/*public function canAddComment() {
		return Mage::getSingleton('admin/session')->isAllowed('nostress/gpwebpay/transactions/actions/comment') && $this->getTransaction()->canComment();
	}
	
	public function getSubmitUrl() {
		return $this->getUrl('* /* /addComment', array('transaction_id'=>$this->getTransaction()->getId()));
	}*/
	
	/**
	* Customer Notification Applicable check method
	*
	* @param  Nostress_Gpwebpay_Model_Transactions_Status_History $history
	* @return boolean
	*/
	public function isCustomerNotificationNotApplicable(Nostress_Gpwebpay_Model_Transactions_Status_History $history) {
		return $history->isCustomerNotificationNotApplicable();
	}
}