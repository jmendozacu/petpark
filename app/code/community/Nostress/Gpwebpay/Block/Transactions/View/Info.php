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
class Nostress_Gpwebpay_Block_Transactions_View_Info extends Nostress_Gpwebpay_Block_Transactions_Abstract
{
	/**
	* Retrieve required options from parent
	*/
	protected function _beforeToHtml() {
		if (!$this->getParentBlock()) {
			Mage::throwException(Mage::helper('adminhtml')->__('Invalid parent block for this block.'));
		}
		$this->setTransaction($this->getParentBlock()->getTransaction());
		
		foreach ($this->getParentBlock()->getTransactionInfoData() as $k => $v) {
			$this->setDataUsingMethod($k, $v);
		}
		
		parent::_beforeToHtml();
	}
	
	public function getTransactionStoreName() {
		if ($this->getTransaction()) {
			$storeId = $this->getTransaction()->getOrder()->getStoreId();
			if (is_null($storeId)) {
				$deleted = Mage::helper('adminhtml')->__(' [deleted]');
				return nl2br($this->getTransaction()->getStoreName()) . $deleted;
			}
			$store = Mage::app()->getStore($storeId);
			$name = array(
				$store->getWebsite()->getName(),
				$store->getGroup()->getName(),
				$store->getName()
			);
			return implode('<br/>', $name);
		}
		return null;
	}
	
	public function getCustomerGroupName() {
		if ($this->getTransaction()->getOrder()) {
			return Mage::getModel('customer/group')->load((int)$this->getTransaction()->getOrder()->getCustomerGroupId())->getCode();
		}
		return null;
	}
	
	public function getCustomerViewUrl() {
		if ($this->getTransaction()->getOrder()->getCustomerIsGuest() || !$this->getTransaction()->getOrder()->getCustomerId()) {
			return false;
		}
		return $this->urlHack($this->getUrl('/*/*/customer/edit/', array('id' => $this->getTransaction()->getOrder()->getCustomerId())));
	}
	
	public function getOrderViewUrl($orderId) {
		return $this->urlHack($this->getUrl('/*/*/sales_order/view', array('order_id'=>$orderId)));
	}
	
	public function getViewUrl($transactionId) {
		return $this->getUrl('nostress_gpwebpay/transactions/view', array('transaction_id'=>$transactionId));
	}
	
	public function urlHack($url) {
		return str_replace("index.php/", "index.php/admin/", $url);
	}
	
	/**
	* Find sort order for account data
	* Sort Order used as array key
	*
	* @param array $data
	* @param int $sortOrder
	* @return int
	*/
	protected function _prepareAccountDataSortOrder(array $data, $sortOrder) {
		if (isset($data[$sortOrder])) {
			return $this->_prepareAccountDataSortOrder($data, $sortOrder + 1);
		}
		return $sortOrder;
	}
	
	/**
	* Return array of additional account data
	* Value is option style array
	*
	* @return array
	*/
	public function getCustomerAccountData() {
		$accountData = array();
		
		/* @var $config Mage_Eav_Model_Config */
		$config     = Mage::getSingleton('eav/config');
		$entityType = 'customer';
		$customer   = Mage::getModel('customer/customer');
		foreach ($config->getEntityAttributeCodes($entityType) as $attributeCode) {
			/* @var $attribute Mage_Customer_Model_Attribute */
			$attribute = $config->getAttribute($entityType, $attributeCode);
			if (!$attribute->getIsVisible() || $attribute->getIsSystem()) {
				continue;
			}
			$orderKey   = sprintf('customer_%s', $attribute->getAttributeCode());
			$orderValue = $this->getTransaction()->getOrder()->getData($orderKey);
			if ($orderValue != '') {
				$customer->setData($attribute->getAttributeCode(), $orderValue);
				$dataModel  = Mage_Customer_Model_Attribute_Data::factory($attribute, $customer);
				$value      = $dataModel->outputValue(Mage_Customer_Model_Attribute_Data::OUTPUT_FORMAT_HTML);
				$sortOrder  = $attribute->getSortOrder() + $attribute->getIsUserDefined() ? 200 : 0;
				$sortOrder  = $this->_prepareAccountDataSortOrder($accountData, $sortOrder);
				$accountData[$sortOrder] = array(
					'label' => $attribute->getFrontendLabel(),
					'value' => $this->escapeHtml($value, array('br'))
				);
			}
		}
		
		ksort($accountData, SORT_NUMERIC);
		
		return $accountData;
	}
}