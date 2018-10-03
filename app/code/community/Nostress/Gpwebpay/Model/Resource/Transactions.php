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
 * Flat transactions modle resource
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Resource_Transactions extends Nostress_Gpwebpay_Model_Resource_Transactions_Abstract
{
	/**
	* Event prefix
	*
	* @var string
	*/
	protected $_eventPrefix                  = 'nostress_gpwebpay_transactions_resource';
	
	/**
	* Event object
	*
	* @var string
	*/
	protected $_eventObject                  = 'resource';
	
	/**
	* Is grid
	*
	* @var boolean
	*/
	protected $_grid                         = true;
	
	/**
	* Use increment id
	*
	* @var boolean
	*/
	protected $_useIncrementId               = true;
	
	/**
	* Entity code for increment id
	*
	* @var string
	*/
	protected $_entityCodeForIncrementId     = 'transactions';
	
	/**
	* Model Initialization
	*
	*/
	protected function _construct() {
		$this->_init('nostress_gpwebpay/transactions', 'entity_id');
	}
	
	/**
	* Init virtual grid records for entity
	*
	* @return Nostress_Gpwebpay_Model_Resource_Transactions
	*/
	protected function _initVirtualGridColumns() {
		parent::_initVirtualGridColumns();
		$adapter       = $this->getReadConnection();
		
		return $this;
	}
	
    /**
     * Count existent products of order items by specified product types
     *
     * @param int $orderId
     * @param array $productTypeIds
     * @param bool $isProductTypeIn
     * @return array
     */
   /* public function aggregateProductsByTypes($orderId, $productTypeIds = array(), $isProductTypeIn = false)
    {
        $adapter = $this->getReadConnection();
        $select  = $adapter->select()
            ->from(
                array('o' => $this->getTable('sales/order_item')),
                array('o.product_type', new Zend_Db_Expr('COUNT(*)')))
            ->joinInner(
                array('p' => $this->getTable('catalog/product')),
                'o.product_id=p.entity_id',
                array())
            ->where('o.order_id=?', $orderId)
            ->group('o.product_type')
        ;
        if ($productTypeIds) {
            $select->where(
                sprintf('(o.product_type %s (?))', ($isProductTypeIn ? 'IN' : 'NOT IN')),
                $productTypeIds);
        }
        return $adapter->fetchPairs($select);
    }*/

    /**
     * Retrieve order_increment_id by order_id
     *
     * @param int $orderId
     * @return string
     */
    /*public function getIncrementId($orderId)
    {
        $adapter = $this->getReadConnection();
        $bind    = array(':entity_id' => $orderId);
        $select  = $adapter->select()
            ->from($this->getMainTable(), array("increment_id"))
            ->where('entity_id = :entity_id');
        return $adapter->fetchOne($select, $bind);
    }*/
}
