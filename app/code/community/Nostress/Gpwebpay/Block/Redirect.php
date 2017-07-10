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
 * Redirect block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Redirect extends Mage_Core_Block_Abstract
{
	
	protected function _toHtml() {
		$init = Mage::getModel('nostress_gpwebpay/abstract');
		$init->GPLog(0);
		$CreateOrderUrl = $init->getCreateOrderUrl();
		$init->createTransaction();
		//Mage::app()->getFrontController()->getResponse()->setRedirect(htmlspecialchars_decode($CreateOrderUrl));
		$form = new Varien_Data_Form();
		$form->setAction($init->getConfig()->getGpwebpayUrl())
			->setId('nostress_gpwebpay_redirect')
			->setName('nostress_gpwebpay_redirect')
			->setMethod('post')
			->setUseContainer(true);
		foreach ($init->getGpwebpayFormFields() as $field=>$value) {
			$form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
		}
		//$form->addField("submit", 'submit', array('value'=>"Submit"));
		
		$html = "<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv=\"refresh\" content=\"20; url=".$CreateOrderUrl."\">
		<meta charset=\"utf-8\" />
	</head>
	<body>
		<h3>".Mage::helper('nostress_gpwebpay')->__('Redirecting to GPWebPay ...')."</h3>".
		Mage::helper('nostress_gpwebpay')->__("In a few seconds you will be redirected to GPWebPay to continue with online payment via GPWebPay.")."<br /><br />
		<h4>".Mage::helper('nostress_gpwebpay')->__('Please ')."<a href=\"".$CreateOrderUrl."\" target=\"_top\">".Mage::helper('nostress_gpwebpay')->__('click here')."</a>".Mage::helper('nostress_gpwebpay')->__(' if you are not redirected automatically.')."</h4>
		".$form->toHtml()."
		<script type=\"text/javascript\">document.getElementById('nostress_gpwebpay_redirect').submit();</script>
	</body>
</html>";
		$init->GPLog("\$CreateOrderUrl = ".$CreateOrderUrl, 1);
		$init->GPLog(1);
		
		return $html;
	}
}