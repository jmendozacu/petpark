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
 * Transaction information tab
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_View_Tab_Info extends Nostress_Gpwebpay_Block_Transactions_Abstract implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	/**
	* Retrieve transaction model instance
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getTransaction() {
		return Mage::registry('current_transaction');
	}
	
	/**
	* Retrieve source model instance
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getSource() {
		return $this->getTransaction();
	}
	
	public function getTransactionInfoData() {
		return array(
			'no_use_order_link' => true,
		);
	}
	
	public function getItemsHtml() {
		return $this->getChildHtml('transactions_items');
	}
	
	public function getPaymentHtml() {
		return $this->getChildHtml('transactions_payment');
	}
	
	public function getViewUrl($transactionId) {
		return $this->getUrl('*/*/*', array('transaction_id'=>$transactionId));
	}
	
	/**
	* ######################## TAB settings #################################
	*/
	public function getTabLabel() {
		return Mage::helper('nostress_gpwebpay')->__('Information');
	}
	
	public function getTabTitle() {
		return Mage::helper('nostress_gpwebpay')->__('Transaction Information');
	}
	
	public function canShowTab() {
		return true;
	}
	
	public function isHidden() {
		return false;
	}
}
