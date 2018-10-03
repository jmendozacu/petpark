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
 * Transactions page content block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions extends Nostress_Gpwebpay_Block_Widget_Grid_Container
{
	public function __construct() {
		$this->_controller = 'transactions';
		$this->_headerText = Mage::helper('nostress_gpwebpay')->__('GPWebPay Transactions');
		parent::__construct();
		if (Mage::getSingleton('admin/session')->isAllowed('nostress/gpwebpay/transactions/actions/batch_close') && Mage::getModel('nostress_gpwebpay/transactions')->canBatchClose()) {
			$this->_addButton('transactions_batch_close', array(
				'label'     => Mage::helper('nostress_gpwebpay')->__('Batch close'),
				'onclick'   => 'setLocation(\''.$this->getUrl('*/*/batchClose').'\')'
			));
		}
	}
}