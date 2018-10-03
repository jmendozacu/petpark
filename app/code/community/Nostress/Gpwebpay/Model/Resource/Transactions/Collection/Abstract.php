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
 * Flat transactions collection abstract
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
abstract class Nostress_Gpwebpay_Model_Resource_Transactions_Collection_Abstract extends Nostress_Gpwebpay_Model_Resource_Collection_Abstract
{
    /**
     * Transaction object
     *
     * @var Nostress_Gpwebpay_Model_Transactions
     */
    protected $_salesTransaction   = null;

    /**
     * Order field for setOrderFilter
     *
     * @var string
     */
    protected $_transactionField   = 'parent_id';

    /**
     * Set sales order model as parent collection object
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Resource_Order_Collection_Abstract
     */
    public function setSalesTransaction($transaction)
    {
        $this->_salesTransaction = $transaction;
        if ($this->_eventPrefix && $this->_eventObject) {
            Mage::dispatchEvent($this->_eventPrefix . '_set_sales_transaction', array(
                'collection' => $this,
                $this->_eventObject => $this,
                'transaction' => $transaction
            ));
        }

        return $this;
    }

    /**
     * Retrieve sales order as parent collection object
     *
     * @return Mage_Sales_Model_Order|null
     */
    public function getSalesTransaction()
    {
        return $this->_salesTransaction;
    }

	/**
	* Add transaction filter
	*
	* @param int|Nostress_Gpwebpay_Model_Transactions $transaction
	* @return Nostress_Gpwebpay_Model_Resource_Transactions_Collection_Abstract
	*/
	public function setTransactionFilter($transaction) {
		if ($transaction instanceof Nostress_Gpwebpay_Model_Transactions) {
			$this->setSalesTransaction($transaction);
			$transactionId = $transaction->getId();
			if ($transactionId) {
				$this->addFieldToFilter($this->_transactionField, $transactionId);
			} else {
				$this->_totalRecords = 0;
				$this->_setIsLoaded(true);
			}
		}
		else {
			$this->addFieldToFilter($this->_transactionField, $transaction);
		}
		return $this;
	}
}
