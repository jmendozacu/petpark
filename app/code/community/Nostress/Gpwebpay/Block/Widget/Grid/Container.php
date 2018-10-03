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
 * Transactions grid container block
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Widget_Grid_Container extends Mage_Adminhtml_Block_Widget_Container
{
	
	/*protected $_blockGroup = 'nostress_gpwebpay';
	
	public function __construct() {
		$this->_blockGroup = 'nostress_gpwebpay';
		parent::__construct();
	}*/
	
	protected $_addButtonLabel;
	protected $_backButtonLabel;
	protected $_blockGroup = 'nostress_gpwebpay';
	
	public function __construct() {
		if (is_null($this->_backButtonLabel)) {
			$this->_backButtonLabel = $this->__('Back');
		}
		
		parent::__construct();
		
		$this->setTemplate('widget/grid/container.phtml');
	}
	
	protected function _prepareLayout() {
		$this->setChild( 'grid',
			$this->getLayout()->createBlock( $this->_blockGroup.'/'.$this->_controller.'_grid',
			$this->_controller.'.grid')->setSaveParametersInSession(true) );
		return parent::_prepareLayout();
	}
	
	public function getGridHtml() {
		return $this->getChildHtml('grid');
	}
	
	protected function getBackButtonLabel() {
		return $this->_backButtonLabel;
	}
	
	protected function _addBackButton() {
		$this->_addButton('back', array(
			'label'     => $this->getBackButtonLabel(),
			'onclick'   => 'setLocation(\'' . $this->getBackUrl() .'\')',
			'class'     => 'back',
		));
	}
	
	public function getHeaderCssClass() {
		return 'icon-head ' . parent::getHeaderCssClass();
	}
	
	public function getHeaderWidth() {
		return 'width:50%;';
	}
}