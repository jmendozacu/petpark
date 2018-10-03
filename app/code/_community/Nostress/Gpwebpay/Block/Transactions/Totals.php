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
 * Transaction totals block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_Totals extends Nostress_Gpwebpay_Block_Transactions_TotalsAbstract
{
	/**
	* Initialize transaction totals array
	*
	* @return Nostress_Gpwebpay_Block_Transactions_Totals
	*/
	protected function _initTotals() {
		parent::_initTotals();
		$this->_totals['paid'] = new Varien_Object(array(
			'code'      => 'paid',
			'strong'    => true,
			'value'     => $this->getSource()->getTotalPaid(),
			'base_value'=> $this->getSource()->getBaseTotalPaid(),
			'label'     => $this->helper('sales')->__('Total Paid'),
			'area'      => 'footer'
		));
		$this->_totals['refunded'] = new Varien_Object(array(
			'code'      => 'refunded',
			'strong'    => true,
			'value'     => $this->getSource()->getTotalRefunded(),
			'base_value'=> $this->getSource()->getBaseTotalRefunded(),
			'label'     => $this->helper('sales')->__('Total Refunded'),
			'area'      => 'footer'
		));
		$this->_totals['due'] = new Varien_Object(array(
			'code'      => 'due',
			'strong'    => true,
			'value'     => $this->getSource()->getTotalDue(),
			'base_value'=> $this->getSource()->getBaseTotalDue(),
			'label'     => $this->helper('sales')->__('Total Due'),
			'area'      => 'footer'
		));
		return $this;
	}
}