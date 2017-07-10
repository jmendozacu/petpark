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
 * Setup Model of Nostress Gpwebpay Module
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Resource_Setup extends Mage_Eav_Model_Entity_Setup
{
	/**
	* List of entities converted from EAV to flat data structure
	*
	* @var $_flatEntityTables array
	*/
	protected $_flatEntityTables     = array(
        'transactions'             => 'nostress_gpwebpay/transactions',
        'transactions_status_history' => 'nostress_gpwebpay/transactions_status_history'
	);
	
	/**
	* List of entities used with separate grid table
	*
	* @var $_flatEntitiesGrid array
	*/
	protected $_flatEntitiesGrid     = array(
		'transactions'
	);
	
    /**
     * Check if table exist for flat entity
     *
     * @param string $table
     * @return bool
     */
    protected function _flatTableExist($table)
    {
        $tablesList = $this->getConnection()->listTables();
        return in_array(strtoupper($this->getTable($table)), array_map('strtoupper', $tablesList));
    }

    /**
     * Add entity attribute. Overwrited for flat entities support
     *
     * @param int|string $entityTypeId
     * @param string $code
     * @param array $attr
     * @return Nostress_Gpwebpay_Model_Resource_Setup
     */
    public function addAttribute($entityTypeId, $code, array $attr)
    {
        if (isset($this->_flatEntityTables[$entityTypeId]) &&
            $this->_flatTableExist($this->_flatEntityTables[$entityTypeId]))
        {
            $this->_addFlatAttribute($this->_flatEntityTables[$entityTypeId], $code, $attr);
            $this->_addGridAttribute($this->_flatEntityTables[$entityTypeId], $code, $attr, $entityTypeId);
        } else {
            parent::addAttribute($entityTypeId, $code, $attr);
        }
        return $this;
    }

    /**
     * Add attribute as separate column in the table
     *
     * @param string $table
     * @param string $attribute
     * @param array $attr
     * @return Nostress_Gpwebpay_Model_Resource_Setup
     */
    protected function _addFlatAttribute($table, $attribute, $attr)
    {
        $tableInfo = $this->getConnection()->describeTable($this->getTable($table));
        if (isset($tableInfo[$attribute])) {
            return $this;
        }
        $columnDefinition = $this->_getAttributeColumnDefinition($attribute, $attr);
        $this->getConnection()->addColumn($this->getTable($table), $attribute, $columnDefinition);
        return $this;
    }

    /**
     * Add attribute to grid table if necessary
     *
     * @param string $table
     * @param string $attribute
     * @param array $attr
     * @param string $entityTypeId
     * @return Nostress_Gpwebpay_Model_Resource_Setup
     */
    protected function _addGridAttribute($table, $attribute, $attr, $entityTypeId)
    {
        if (in_array($entityTypeId, $this->_flatEntitiesGrid) && !empty($attr['grid'])) {
            $columnDefinition = $this->_getAttributeColumnDefinition($attribute, $attr);
            $this->getConnection()->addColumn($this->getTable($table . '_grid'), $attribute, $columnDefinition);
        }
        return $this;
    }

    /**
     * Retrieve definition of column for create in flat table
     *
     * @param string $code
     * @param array $data
     * @return array
     */
    protected function _getAttributeColumnDefinition($code, $data)
    {
        // Convert attribute type to column info
        $data['type'] = isset($data['type']) ? $data['type'] : 'varchar';
        $type = null;
        $length = null;
        switch ($data['type']) {
            case 'timestamp':
                $type = Varien_Db_Ddl_Table::TYPE_TIMESTAMP;
                break;
            case 'datetime':
                $type = Varien_Db_Ddl_Table::TYPE_DATETIME;
                break;
            case 'decimal':
                $type = Varien_Db_Ddl_Table::TYPE_DECIMAL;
                $length = '12,4';
                break;
            case 'int':
                $type = Varien_Db_Ddl_Table::TYPE_INTEGER;
                break;
            case 'text':
                $type = Varien_Db_Ddl_Table::TYPE_TEXT;
                $length = 65536;
                break;
            case 'char':
            case 'varchar':
                $type = Varien_Db_Ddl_Table::TYPE_TEXT;
                $length = 255;
                break;
        }
        if ($type !== null) {
            $data['type'] = $type;
            $data['length'] = $length;
        }

        $data['nullable'] = isset($data['required']) ? !$data['required'] : true;
        $data['comment']  = isset($data['comment']) ? $data['comment'] : ucwords(str_replace('_', ' ', $code));
        return $data;
    }

    /**
     * Retrieve default entities
     *
     * @return array
     */
    public function getDefaultEntities()
    {
        return array(
            'transactions' => array(
                'entity_model'          => 'nostress_gpwebpay/transactions',
                'table'                 => 'nostress_gpwebpay/transactions',
                'increment_model'       => 'eav/entity_increment_numeric',
                'increment_per_store'   =>true,
                'attributes'            => array(
                    'entity_id'                 => array('type'=>'static'),
                    'order_id'                  => array('type'=>'static'),
                    'real_order_id'             => array('type'=>'varchar'),
                    'status'                    => array('type'=>'varchar'),
                    'state'                     => array('type'=>'varchar'),
                    'deposited'                 => array('type'=>'decimal'),
                    'credited'                  => array('type'=>'decimal'),
                    'credit_id'                 => array('type'=>'int'),
                ),
            ),
            'transactions_status_history' => array(
                'entity_model'      => 'nostress_gpwebpay/transactions_status_history',
                'table'=>'nostress_gpwebpay/transactions_entity',
                'attributes' => array(
                    'parent_id' => array(
                        'type'=>'static'
                    ),
                    'status'    => array('type'=>'varchar'),
                    'comment'   => array('type'=>'text'),
                    'is_customer_notified' => array('type'=>'int'),
                ),
            )
        );
    }
}
