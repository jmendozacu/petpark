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
 * Adminhtml Temporary Shutdown block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Adminhtml_Tempshutdown extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	
	protected $_addRowButtonHtml = array();
	protected $_removeRowButtonHtml = array();
	
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		$this->setElement($element);
		
		$html = '
		<div class="grid" >
			<table class="border" cellspacing="0" cellpadding="0">
				<tbody id="tempshutdown_container">
					<tr class="headings">
						<th>'.Mage::helper('nostress_gpwebpay')->__('Date from').'</th>
						<th>'.Mage::helper('nostress_gpwebpay')->__('Date to').'</th>
						<th>'.Mage::helper('nostress_gpwebpay')->__('Active').'</th>
					</tr>
					'.$this->_getRowTemplateHtml().'
				</tbody>
			</table>
		</div>';
		
		return $html;	
	}
    
	protected function _getRowTemplateHtml() {
		$html = '
		<tr>
			<td style="display: table-cell; min-width: 150px;">'.$this->_getDateField(0).'</td>
			<td style="display: table-cell; min-width: 150px;">'.$this->_getDateField(1).'</td>
			<td><input type="checkbox" value="1" name="'.$this->getElement()->getName().'[active]" id="'.$this->getElement()->getId().'_active"'.$this->_getChecked().' /></td>
		</tr>';
		
		return $html;
	}
	
	protected function _getDateField($type = 0) {
		$divId = $this->getElement()->getId();
		
		$html = "
		<input name=\"".$this->getElement()->getName()."[".$type."]\" id=\"".$divId."_date".$type."\" value=\"".$this->_getValue($type)."\" type=\"text\" style=\"width:110px !important; float: left; margin-right: 10px;\" />
		<img src=\"".$this->getSkinUrl('images/grid-cal.gif')."\" alt=\"\" id=\"".$divId."_date_trig".$type."\" title=\"".Mage::helper('nostress_gpwebpay')->__('Select Date')."\" />
		<script type=\"text/javascript\">
			//<![CDATA[
			//this example uses dd.MM.yyyy hh:mm format.
			Calendar.setup({
				inputField: \"".$divId."_date".$type."\",
				//ifFormat: \"%d.%m.%Y %H:%M\",
				ifFormat: \"%m/%d/%Y\",
				//showsTime: true,
				firstDay: 1,
				//timeFormat: \"24\",
				button: \"".$divId."_date_trig".$type."\",
				align: \"Bl\",
				singleClick : true
			});
			//]]>
		</script>";
		
		return $html;
	}
	
	protected function _getChecked() {
		return ($this->_getValue("active") == 1) ? ' checked="checked"' : '';
	}
    
	protected function _getValue($key) {
		return $this->getElement()->getData('value/'.$key);
	}

}