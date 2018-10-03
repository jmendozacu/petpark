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
 * Transaction items grid
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_View_Items extends Nostress_Gpwebpay_Block_Transactions_Items_Abstract
{
	/**
	* Retrieve required options from parent
	*/
	protected function _beforeToHtml() {
		if (!$this->getParentBlock()) {
			Mage::throwException(Mage::helper('adminhtml')->__('Invalid parent block for this block'));
		}
		$this->setOrder($this->getParentBlock()->getOrder());
		parent::_beforeToHtml();
	}
	
	/**
	* Retrieve order items collection
	*
	* @return unknown
	*/
	public function getItemsCollection() {
		return $this->getOrder()->getItemsCollection();
	}
}