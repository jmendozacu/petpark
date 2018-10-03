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
 * Transactions grid
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Block_Transactions_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	
	public function __construct() {
		parent::__construct();
		$this->setId('nostress_gpwebpay_transactions_grid');
		$this->setUseAjax(true);
		$this->setDefaultSort('created_at');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
	}
	
	/**
	* Retrieve collection class
	*
	* @return string
	*/
	protected function _getCollectionClass() {
		return 'nostress_gpwebpay/transactions_grid_collection';
	}
	
	protected function _prepareCollection() {
		$collection = Mage::getResourceModel($this->_getCollectionClass());
		$collection->join('sales/order_grid', 'increment_id=real_order_id', array('store_id'=>'store_id', 'ordered_at' =>'created_at', 'billing_name'=>'billing_name', 'base_grand_total'=>'base_grand_total', 'base_currency_code'=>'base_currency_code', 'grand_total'=>'grand_total', 'order_currency_code'=>'order_currency_code', 'order_status'=>'status' ), null,'left');
		$this->setCollection($collection);
		//Mage::getModel('nostress_gpwebpay/abstract')->fireLog(parent::_prepareCollection());
		return parent::_prepareCollection();
	}
	
	protected function _prepareColumns() {
		$this->addColumn('real_transaction_id', array(
			'header'=> Mage::helper('nostress_gpwebpay')->__('Transaction #'),
			'width' => '80px',
			'type'  => 'text',
			'index' => 'entity_id',
		));
		
		$this->addColumn('real_order_id', array(
			'header'=> Mage::helper('sales')->__('Order #'),
			'width' => '80px',
			'type'  => 'text',
			'index' => 'real_order_id',
		));
		
		if (!Mage::app()->isSingleStoreMode()) {
			$this->addColumn('store_id', array(
				'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
				'index'     => 'store_id',
				'type'      => 'store',
				'store_view'=> true,
				'display_deleted' => true,
			));
		}
		
		$this->addColumn('created_at', array(
			'header' => Mage::helper('nostress_gpwebpay')->__('Created On'),
			'index' => 'created_at',
			'type' => 'datetime',
			'width' => '100px',
		));
		
		$this->addColumn('ordered_at', array(
			'header' => Mage::helper('nostress_gpwebpay')->__('Ordered On'),
			'index' => 'ordered_at',
			'type' => 'datetime',
			'width' => '100px',
		));
		$this->addColumn('updated_at', array(
			'header' => Mage::helper('nostress_gpwebpay')->__('State Last Checked On'),
			'index' => 'updated_at',
			'type' => 'datetime',
			'width' => '100px',
		));
		
		$this->addColumn('billing_name', array(
			'header' => Mage::helper('sales')->__('Bill to Name'),
			'index' => 'billing_name',
		));
		
		$this->addColumn('base_grand_total', array(
			'header' => Mage::helper('sales')->__('G.T. (Base)'),
			'index' => 'base_grand_total',
			'type'  => 'currency',
			'currency' => 'base_currency_code',
		));
		
		$this->addColumn('grand_total', array(
			'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
			'index' => 'grand_total',
			'type'  => 'currency',
			'currency' => 'order_currency_code',
		));
		
		$this->addColumn('order_status', array(
			'header' => Mage::helper('nostress_gpwebpay')->__('Order Status'),
			'index' => 'order_status',
			'type'  => 'options',
			'width' => '70px',
			'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
		));
		
		$this->addColumn('status', array(
			'header' => Mage::helper('nostress_gpwebpay')->__('Transaction Status'),
			'index' => 'status',
			'type'  => 'options',
			'width' => '70px',
			'options' => Mage::getSingleton('nostress_gpwebpay/transactions_config')->getStatuses(),
		));
		
		if (Mage::getSingleton('admin/session')->isAllowed('nostress/gpwebpay/transactions/actions/view')) {
			$this->addColumn('action', array(
				'header'    => Mage::helper('sales')->__('Action'),
				'width'     => '50px',
				'type'      => 'action',
				'getter'     => 'getId',
				'actions'   => array(array(
					'caption' => Mage::helper('sales')->__('View'),
					'url'     => array('base'=>'*/transactions/view'),
					'field'   => 'transaction_id'
				)),
				'filter'    => false,
				'sortable'  => false,
				'index'     => 'stores',
				'is_system' => true,
			));
		}
		//$this->addRssList('rss/nostress_gpwebpay_transactions/new', Mage::helper('nostress_gpwebpay')->__('New Transactions RSS'));
		
		//$this->addExportType('*/*/exportCsv', Mage::helper('nostress_gpwebpay')->__('CSV'));
		//$this->addExportType('*/*/exportExcel', Mage::helper('nostress_gpwebpay')->__('Excel XML'));
		
		return parent::_prepareColumns();
	}
	
	protected function _prepareMassaction() {
		$this->setMassactionIdField('entity_id');
		$this->getMassactionBlock()->setFormFieldName('transaction_ids');
		$this->getMassactionBlock()->setUseSelectAll(false);
		
		if (Mage::getSingleton('admin/session')->isAllowed('nostress/gpwebpay/transactions/actions/query_order_state')) {
			$this->getMassactionBlock()->addItem('query_order_state', array(
				'label'=> Mage::helper('nostress_gpwebpay')->__('Query State'),
				'url'  => $this->getUrl('*/transactions/massQueryOrderState'),
			));
		}
		
		return $this;
	}
	
	public function getRowUrl($row) {
		if (Mage::getSingleton('admin/session')->isAllowed('nostress/gpwebpay/transactions/actions/view')) {
			return $this->getUrl('nostress_gpwebpay/transactions/view', array('transaction_id' => $row->getId()));
		}
		return false;
	}
	
	public function getGridUrl() {
		return $this->getUrl('*/*/grid', array('_current'=>true));
	}
	
}