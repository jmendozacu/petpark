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
 * Transaction Totals abstract block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_TotalsAbstract extends Nostress_Gpwebpay_Block_Transactions_TotalsBase
{
	/**
	* Format total value based on order currency
	*
	* @param   Varien_Object $total
	* @return  string
	*/
	public function formatValue($total) {
		if (!$total->getIsFormated()) {
			return $this->helper('adminhtml/sales')->displayPrices(
				$this->getOrder(),
				$total->getBaseValue(),
				$total->getValue()
			);
		}
		return $total->getValue();
	}
	
	/**
	* Initialize order totals array
	*
	* @return Nostress_Gpwebpay_Block_Transactions_Totals
	*/
	protected function _initTotals() {
		$this->_totals = array();
		$this->_totals['subtotal'] = new Varien_Object(array(
			'code'      => 'subtotal',
			'value'     => $this->getSource()->getSubtotal(),
			'base_value'=> $this->getSource()->getBaseSubtotal(),
			'label'     => $this->helper('sales')->__('Subtotal')
		));
		
		/**
		* Add shipping
		*/
		if (!$this->getSource()->getIsVirtual() && ((float) $this->getSource()->getShippingAmount() || $this->getSource()->getShippingDescription())) {
			$this->_totals['shipping'] = new Varien_Object(array(
				'code'      => 'shipping',
				'value'     => $this->getSource()->getShippingAmount(),
				'base_value'=> $this->getSource()->getBaseShippingAmount(),
				'label' => $this->helper('sales')->__('Shipping & Handling')
			));
		}
		
		/**
		* Add discount
		*/
		if (((float)$this->getSource()->getDiscountAmount()) != 0) {
			if ($this->getSource()->getDiscountDescription()) {
				$discountLabel = $this->helper('sales')->__('Discount (%s)', $this->getSource()->getDiscountDescription());
			}
			else {
				$discountLabel = $this->helper('sales')->__('Discount');
			}
			$this->_totals['discount'] = new Varien_Object(array(
				'code'      => 'discount',
				'value'     => $this->getSource()->getDiscountAmount(),
				'base_value'=> $this->getSource()->getBaseDiscountAmount(),
				'label'     => $discountLabel
			));
		}
		
		$this->_totals['grand_total'] = new Varien_Object(array(
			'code'      => 'grand_total',
			'strong'    => true,
			'value'     => $this->getSource()->getGrandTotal(),
			'base_value'=> $this->getSource()->getBaseGrandTotal(),
			'label'     => $this->helper('sales')->__('Grand Total'),
			'area'      => 'footer'
		));
		
		return $this;
	}
}