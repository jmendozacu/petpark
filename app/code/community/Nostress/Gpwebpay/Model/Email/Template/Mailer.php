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
 * Email Template Mailer Model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Email_Template_Mailer extends Mage_Core_Model_Email_Template_Mailer
{
	
	private $Attachement = null;
	private $AttachementName = "";
	
	public function addAttachment($pdf, $name = "order.pdf") {
		try {
			$file = $pdf->render();
			$this->Attachement = $file;
			$this->AttachementName = $name.".pdf";
		} catch (Exception $e) {
			Mage::log("Caught error while attaching pdf: ".$e->getMessage());
		}
		return $this;
	}
	
	public function send() {
        $emailTemplate = Mage::getModel('core/email_template');
        // Send all emails from corresponding list
        while (!empty($this->_emailInfos)) {
            $emailInfo = array_pop($this->_emailInfos);
            // Handle "Bcc" recepients of the current email
            $emailTemplate->addBcc($emailInfo->getBccEmails());
            if ($this->Attachement != null) {
			$emailTemplate->getMail()->createAttachment($this->Attachement,'application/pdf',Zend_Mime::DISPOSITION_ATTACHMENT,Zend_Mime::ENCODING_BASE64,$this->AttachementName);
            }
            // Set required design parameters and delegate email sending to Mage_Core_Model_Email_Template
            $emailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->getStoreId()))
                ->sendTransactional(
                $this->getTemplateId(),
                $this->getSender(),
                $emailInfo->getToEmails(),
                $emailInfo->getToNames(),
                $this->getTemplateParams(),
                $this->getStoreId()
            );
        }
        return $this;
    }

}