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
 * Version helper
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Helper_Version extends Mage_Payment_Helper_Data
{
	protected $_moduleLabel = 'GPWebPay';
	protected $_moduleVersion = '6.0.0.2';
	protected $_validForVersion = '1.4+';
	const VRE = "/[0-9]+.[0-9]+/";
	protected $_s = 'mage123';
	
	public function getModuleStatusHtml() {
		Mage::getModel('nostress_gpwebpay/abstract')->GPLog(0);
		if ($this->isVersionValid()) {
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("The licence is valid");
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
			return "<span style='color:green;font-weight:bold'>".Mage::helper('nostress_gpwebpay')->__('OK')."</span>";			
		}
		else {
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("The licence is invalid!");
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
			return "<span style='color:red;font-weight:bold'>".Mage::helper('nostress_gpwebpay')->__('Not Valid')." - <a href=\"".Mage::helper('nostress_gpwebpay')->__('http://www.nostresscommerce.com/buy-new-license.html')."\">".Mage::helper('nostress_gpwebpay')->__('Buy New License')."</a></span>";
		}
		Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
	}
	
	public function getLicenseKeyStatusHtml() {
		Mage::getModel('nostress_gpwebpay/abstract')->GPLog(0);
		if (!$this->getLicenseKey()) {
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("The licence is invalid!");
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
			return "<span style='color:red;font-weight:bold'>".Mage::helper('nostress_gpwebpay')->__('Please enter the License key')."</span>";
		}
		if ($this->isLicenseKeyValid()) {
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("The licence is valid");
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
			return "<span style='color:green;font-weight:bold'>".Mage::helper('nostress_gpwebpay')->__('OK')."</span>";			
		}
		else {
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog("The licence is invalid!");
			Mage::getModel('nostress_gpwebpay/abstract')->GPLog(1);
			return "<span style='color:red;font-weight:bold'>".Mage::helper('nostress_gpwebpay')->__('Not Valid')." - <a href=\"".Mage::helper('nostress_gpwebpay')->__('http://www.nostresscommerce.com/buy-new-license.html')."\">".Mage::helper('nostress_gpwebpay')->__('Buy New License')."</a></span>";
		}
	}
	
	public function isModuleValid() {
		return $this->isVersionValid() && $this->isLicenseKeyValid();
	}
	
	public function isVersionValid() {
		$curVersion = substr($this->getMagentoVersion(),2,1);
		$match = array();
		if(preg_match(self::VRE ,Mage::getVersion(),$match))
			$match = explode(".",$match[0]);
		if(isset($match[0]) && $match[0] < 1)
			return false;
		if(isset($match[1]))
			$curVersion = $match[1];
	
		switch($this->_validForVersion)
		{
			case '3':
				return $curVersion == $this->_validForVersion;
				break;
			default:
				if ((float)$curVersion >= (float)'4')
					return true;
				break;
		}
		return false;
	}
	
	public function getMagentoVersion()
	{
		return Mage::getVersion();
	}
	
// 	public function isVersionValid() {
// 		return substr(Mage::getVersion(),0,3) == $this->_validForVersion;
// 	}
	
	public function isLicenseKeyValid() {
		return $this->getLicenseKey() ===  $this->generateLicenseKey();
	}
	
	public function getLicenseKey() {
		return Mage::getStoreConfig('nostress_modules/nostress_dashboard/license_key_'.$this->_getModuleName());
	}
	
	public function generateLicenseKey() {				
		$url = parse_url(Mage::app()->getStore(0)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
		return md5(sha1($url['host'].$this->_getModuleName().substr($this->_moduleVersion,0,1).$this->_s));
	}
	
	public function getInvalidModuleConfigHtml() {
		$url = Mage::getModel('adminhtml/url');		
		$elementId = $this->_getModuleName();
		
		$html = "<div class=\"entry-edit-head collapseable\" >";
		$html .= "<a id=\"{$elementId}-head\" href=\"#\" onclick=\"Fieldset.toggleCollapse('{$elementId}', '".$url->getUrl('*/*/state')."'); return false;\">{$this->getModuleLabel()}</a>";
		$html .= "</div>";
		$html .= "<input id=\"{$elementId}-state\" name=\"config_state[{$elementId}]\" type=\"hidden\" value=\"0\" />";
		$html .= "<fieldset class=\"config collapseable\" id=\"{$elementId}\"><legend>{$this->getModuleLabel()}</legend>";
		$html .= "<span style='color:red;font-weight:bold'>".Mage::helper('nostress_gpwebpay')->__('Your License is not valid')." - <a href=\"".Mage::helper('nostress_gpwebpay')->__('http://www.nostresscommerce.com/buy-new-license.html')."\">".Mage::helper('nostress_gpwebpay')->__('Buy New License')."</a></span>";
		$html .= "<br/>";
		$html .= Mage::helper('nostress_gpwebpay')->__('Module is deactivated, more information is available');
		$html .= " <a href=\"".$url->getUrl('*/*/*',array('section'=>'nostress_modules'))."\">".Mage::helper('nostress_gpwebpay')->__('here')."</a>";
		$html .= "</fieldset>";
		$html .= Mage::helper('adminhtml/js')->getScript("Fieldset.applyCollapse('{$elementId}')");
		return $html; 
	}
	
	public function getDashboardFooterHtml() {
		return "
			<tr>
				<td colspan='4'>&copy; <a href=\"".Mage::helper('nostress_gpwebpay')->__("http://www.nostresscommerce.com")."\">NoStress Commerce</a> 2012</td>    			
			</tr>
		</table>"
		.Mage::helper('adminhtml/js')->getScript("
			$('nostress_modules_nostress_dashboard-state').value = 1;       
			$('nostress_modules_nostress_dashboard-head').setStyle('background: none;');
			$('nostress_modules_nostress_dashboard-head').writeAttribute('onclick', 'return false;');
			$('nostress_modules_nostress_dashboard').show();
		");
	}
	
	public function getDashboardHeaderHtml() {
		return "
		<table cellspacing=\"15\">
			<tr>
				<th>".Mage::helper('nostress_gpwebpay')->__('Module Name')."</th>
				<th>".Mage::helper('nostress_gpwebpay')->__('Module Version')."</th>
				<th>".Mage::helper('nostress_gpwebpay')->__('Your License for Magento')."</th>
				<th>".Mage::helper('nostress_gpwebpay')->__('Module Status')."</th>
				<th>".Mage::helper('nostress_gpwebpay')->__('Enter License Key')."</th>
				<th>".Mage::helper('nostress_gpwebpay')->__('License Status')."</th>
			</tr>";
	}
	
	public function getDashboardEntryHtml() {
		$url = Mage::getModel('adminhtml/url');
		return "
		<tr>
			<td><a href=\"".$url->getUrl('*/*/*',array('section'=>'payment'))."#{$this->_getModuleName()}-head\">{$this->getModuleLabel()}</a></td>
			<td>{$this->_moduleVersion}</td>
			<td>{$this->_validForVersion}</td>
			<td>{$this->getModuleStatusHtml()}</td>
			<td><input id=\"{$this->_getModuleName()}_licensekey\" class=\"input-text\" type=\"text\" value=\"".$this->getLicenseKey()."\" name=\"groups[nostress_dashboard][fields][license_key_{$this->_getModuleName()}][value]\" /></td>
			<td>{$this->getLicenseKeyStatusHtml()}</td>    		
		</tr>";
	}
	
	public function getModuleLabel() {
		return $this->__($this->_moduleLabel);
	}
}
