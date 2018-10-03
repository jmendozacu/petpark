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
 * Order model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Order extends Mage_Sales_Model_Order {
	/**
	* Send email with order data
	*
	* @return Nostress_Gpwebpay_Model_Order
	*/
	public function sendNewOrderEmail() {
		$init = Mage::getModel('nostress_gpwebpay/abstract');
		$init->GPLog(0);
		$storeId = $this->getStore()->getId();
		
		if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
			return $this;
		}
		// Get the destination email addresses to send copies to
		$copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
		$copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);
		
		// Start store emulation process
		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
		
		try {
			// Retrieve specified view block from appropriate design package (depends on emulated store)
			$paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
				->setIsSecureMode(true);
			$paymentBlock->getMethod()->setStore($storeId);
			$paymentBlockHtml = $paymentBlock->toHtml();
		} catch (Exception $exception) {
			// Stop store emulation process
			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
			throw $exception;
		}
		
		// Stop store emulation process
		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
		
		// Retrieve corresponding email template id and customer name
		if ($this->getCustomerIsGuest()) {
			$templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
			$customerName = $this->getBillingAddress()->getName();
		} else {
			$templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
			$customerName = $this->getCustomerName();
		}
		
		$mailer = Mage::getModel('core/email_template_mailer');
		$emailInfo = Mage::getModel('core/email_info');
		$emailInfo->addTo($this->getCustomerEmail(), $customerName);
		if ($copyTo && $copyMethod == 'bcc') {
			// Add bcc to customer email
			foreach ($copyTo as $email) {
				$emailInfo->addBcc($email);
			}
		}
		$mailer->addEmailInfo($emailInfo);
		
		// Email copies are sent as separated emails if their copy method is 'copy'
		if ($copyTo && $copyMethod == 'copy') {
			foreach ($copyTo as $email) {
				$emailInfo = Mage::getModel('core/email_info');
				$emailInfo->addTo($email);
				$mailer->addEmailInfo($emailInfo);
			}
		}
		
		foreach ($this->_invoices as $invoice) {
			$pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
			$mailer->addAttachment($pdf, Mage::helper('sales')->__('Invoice')."_".$this->getIncrementId());
		}
		
		// Set all required params and send emails
		$mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
		$mailer->setStoreId($storeId);
		$mailer->setTemplateId($templateId);
		$mailer->setTemplateParams(array(
			'order'        => $this,
			'billing'      => $this->getBillingAddress(),
			'payment_html' => $paymentBlockHtml
		));
		$mailer->send();
		
		$this->setEmailSent(true);
		$this->_getResource()->saveAttribute($this, 'email_sent');
		
		return $this;
	}
}