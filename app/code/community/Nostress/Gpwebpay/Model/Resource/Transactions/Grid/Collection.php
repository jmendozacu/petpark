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
 * Flat Nostress Gpwebpay transactions grid collection
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Resource_Transactions_Grid_Collection extends Nostress_Gpwebpay_Model_Resource_Transactions_Collection
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix    = 'nostress_gpwebpay_transactions_grid_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject    = 'transactions_grid_collection';

    /**
     * Customer mode flag
     * 
     * @var bool
     */
    protected $_customerModeFlag = false;

	/**
	* Model initialization
	*
	*/
	protected function _construct() {
		//@array_walk(debug_backtrace(),create_function('$a,$b', 'print "<br /><b>". basename( $a[\'file\'] ). "</b> &nbsp; <font color=\"red\">{$a[\'line\']}</font> &nbsp; <font color=\"green\">{$a[\'function\']} ()</font> &nbsp; -- ". dirname( $a[\'file\'] ). "/";'));
		parent::_construct();
		$this->setMainTable('nostress_gpwebpay/transactions_grid');
	}

    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $unionSelect = clone $this->getSelect();

        $unionSelect->reset(Zend_Db_Select::ORDER);
        $unionSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $unionSelect->reset(Zend_Db_Select::LIMIT_OFFSET);

        $countSelect = clone $this->getSelect();
        $countSelect->reset();
        $countSelect->from(array('a' => $unionSelect), 'COUNT(*)');

        return $countSelect;
    }

    /**
     * Set customer mode flag value
     *
     * @param bool $value
     * @return Nostress_Gpwebpay_Model_Resource_Transactions_Grid_Collection
     */
    public function setIsCustomerMode($value)
    {
        $this->_customerModeFlag = (bool)$value;
        return $this;
    }

    /**
     * Get customer mode flag value
     *
     * @return bool
     */
    public function getIsCustomerMode()
    {
        return $this->_customerModeFlag;
    }
}
