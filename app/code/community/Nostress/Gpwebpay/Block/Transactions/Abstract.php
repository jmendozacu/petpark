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
 * Transaction abstract block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_Abstract extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
	/**
	* Retrieve available transaction
	*
	* @return Nostress_Gpwebpay_Model_Transactions
	*/
	public function getTransaction() {
		if (Mage::registry('current_transaction')) {
			return Mage::registry('current_transaction');
		}
		if (Mage::registry('transaction')) {
			return Mage::registry('transaction');
		}
		Mage::throwException(Mage::helper('nostress_gpwebpay')->__('Cannot get transaction instance'));
	}
	
	public function getOrder() {
		if ($this->getTransaction()) {
			return $this->getTransaction()->getOrder();
		}
		Mage::throwException(Mage::helper('sales')->__('Cannot get order instance'));
	}
	
	/**
	* Retrieve transaction info block settings
	*
	* @return array
	*/
	public function getTransactionInfoData() {
		return array();
	}
}
