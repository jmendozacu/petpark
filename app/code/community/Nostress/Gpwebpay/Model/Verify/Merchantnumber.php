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
 * Verify merchant number model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Verify_Merchantnumber extends Mage_Core_Model_Config_Data
{
	protected function _beforeSave() {
		Mage::getModel('nostress_gpwebpay/abstract')->GPLog(0);
		$value = $this->getValue();
		
		if (!empty($value)) {
			if (!preg_match("/^[0-9]*$/", $value ))
				Mage::throwException("Error: Merchant Number is not valid!");
		}
		
		Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
		return $this;
    }

}
