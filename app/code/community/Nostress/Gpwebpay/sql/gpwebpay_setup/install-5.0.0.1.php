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
 * SQL installation script
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */

/** @var $installer Nostress_Gpwebpay_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

$statuses = array();
$states = array();

$statuses[] = array(
	'status' => 'approved',
	'label' => 'Authorized'
);
$statuses[] = array(
	'status' => 'approve_reversed',
	'label' => 'Reversed'
);
$statuses[] = array(
	'status' => 'created',
	'label' => 'Not completed (Created)'
);
$statuses[] = array(
	'status' => 'credited_batch_closed',
	'label' => 'Credited (Closed)'
);
$statuses[] = array(
	'status' => 'credited_batch_opened',
	'label' => 'Credited (Opened)'
);
$statuses[] = array(
	'status' => 'declined',
	'label' => 'Declined'
);
$statuses[] = array(
	'status' => 'deleted',
	'label' => 'Deleted'
);
$statuses[] = array(
	'status' => 'deposit_batch_closed',
	'label' => 'Processed'
);
$statuses[] = array(
	'status' => 'deposit_batch_opened',
	'label' => 'Payed'
);
$statuses[] = array(
	'status' => 'order_closed',
	'label' => 'Closed'
);
$statuses[] = array(
	'status' => 'pending',
	'label' => 'Not completed (Pending)'
);
$statuses[] = array(
	'status' => 'requested',
	'label' => 'Not completed (Requested)'
);
$statuses[] = array(
	'status' => 'unapproved',
	'label' => 'Not authorized'
);

$states[] = array(
	'status' => 'approved',
	'state' => 'approved',
	'is_default' => 1
);
$states[] = array(
	'status' => 'approve_reversed',
	'state' => 'approve_reversed',
	'is_default' => 1
);
$states[] = array(
	'status' => 'created',
	'state' => 'created',
	'is_default' => 1
);
$states[] = array(
	'status' => 'credited_batch_closed',
	'state' => 'credited_batch_closed',
	'is_default' => 1
);
$states[] = array(
	'status' => 'credited_batch_opened',
	'state' => 'credited_batch_opened',
	'is_default' => 1
);
$states[] = array(
	'status' => 'declined',
	'state' => 'declined',
	'is_default' => 1
);
$states[] = array(
	'status' => 'deleted',
	'state' => 'deleted',
	'is_default' => 1
);
$states[] = array(
	'status' => 'deposit_batch_closed',
	'state' => 'deposit_batch_closed',
	'is_default' => 1
);
$states[] = array(
	'status' => 'deposit_batch_opened',
	'state' => 'deposit_batch_opened',
	'is_default' => 1
);
$states[] = array(
	'status' => 'order_closed',
	'state' => 'order_closed',
	'is_default' => 1
);
$states[] = array(
	'status' => 'pending',
	'state' => 'pending',
	'is_default' => 1
);
$states[] = array(
	'status' => 'requested',
	'state' => 'requested',
	'is_default' => 1
);
$states[] = array(
	'status' => 'unapproved',
	'state' => 'unapproved',
	'is_default' => 1
);

/**
 * Create table 'nostress_gpwebpay/transactions'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/transactions'))
	->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		), 'Entity Id')
	->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		), 'Order Id')
	->addColumn('real_order_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
		), 'Real Order Id')
	->addColumn('state', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		), 'State')
	->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		), 'Status')
	->addColumn('deposited', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
		), 'Deposited amount')
	->addColumn('credited', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
		), 'Credited amount')
	->addColumn('credit_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		), 'Id of the last credit request')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Created At')
	->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Updated At')
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions', array('status')),
		array('status'))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions', array('state')),
		array('state'))
	->addIndex(
		$installer->getIdxName(
			'nostress_gpwebpay/transactions',
			array('entity_id'),
			Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
		),
		array('entity_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions', array('created_at')),
		array('created_at'))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions', array('order_id')),
		array('order_id'))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions', array('real_order_id')),
		array('real_order_id'))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions', array('updated_at')),
		array('updated_at'))
	->setComment('Nostress Gpwebpay Flat Transactions');
$installer->getConnection()->createTable($table);

/**
 * Create table 'nostress_gpwebpay/transactions_grid'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/transactions_grid'))
	->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		), 'Entity Id')
	->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		), 'Order Id')
	->addColumn('real_order_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
		), 'Real Order Id')
	->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		), 'Status')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Created At')
	->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Updated At')
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions_grid', array('status')),
		array('status'))
	->addIndex(
		$installer->getIdxName(
			'nostress_gpwebpay/transactions_grid',
			array('entity_id'),
			Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
		),
		array('entity_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions_grid', array('created_at')),
		array('created_at'))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions_grid', array('updated_at')),
		array('updated_at'))
	->addForeignKey($installer->getFkName('nostress_gpwebpay/transactions_grid', 'entity_id', 'nostress_gpwebpay/transactions', 'entity_id'),
		'entity_id', $installer->getTable('nostress_gpwebpay/transactions'), 'entity_id',
		Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
	->setComment('Nostress Gpwebpay Flat Transactions Grid');
$installer->getConnection()->createTable($table);

/**
 * Create table 'nostress_gpwebpay/transactions_status_history'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/transactions_status_history'))
	->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		), 'Entity Id')
	->addColumn('parent_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
		), 'Parent Id')
	->addColumn('is_customer_notified', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		), 'Is Customer Notified')
	->addColumn('is_visible_on_front', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
		'unsigned'  => true,
		'nullable'  => false,
		'default'   => '0',
		), 'Is Visible On Front')
	->addColumn('comment', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
		), 'Comment')
	->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		), 'Status')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Created At')
	->addColumn('entity_name', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		), 'Shows what entity history is bind to')
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions_status_history', array('parent_id')),
		array('parent_id'))
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions_status_history', array('created_at')),
		array('created_at'))
	->addForeignKey($installer->getFkName('nostress_gpwebpay/transactions_status_history', 'parent_id', 'nostress_gpwebpay/transactions', 'entity_id'),
		'parent_id', $installer->getTable('nostress_gpwebpay/transactions'), 'entity_id',
		Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
	->setComment('Sales Flat Order Status History');
$installer->getConnection()->createTable($table);

/**
 * Create table 'nostress_gpwebpay/transactions_status'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/transactions_status'))
	->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		'nullable'  => false,
		'primary'   => true,
		), 'Status')
	->addColumn('label', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(
		'nullable'  => false,
		), 'Label')
	->setComment('Nostress Gpwebpay Transactions Status Table');
$installer->getConnection()->createTable($table);

$installer->getConnection()->insertArray(
	$installer->getTable('nostress_gpwebpay/transactions_status'),
	array('status', 'label'),
	$statuses
);

/**
 * Create table 'nostress_gpwebpay/transactions_status_state'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/transactions_status_state'))
	->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		'nullable'  => false,
		'primary'   => true,
		), 'Status')
	->addColumn('state', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		'nullable'  => false,
		'primary'   => true,
		), 'Label')
	->addColumn('is_default', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
		'unsigned'  => true,
		'nullable'  => false,
		'default'   => '0',
		), 'Is Default')
	->addForeignKey($installer->getFkName('nostress_gpwebpay/transactions_status_state', 'status', 'nostress_gpwebpay/transactions_status', 'status'),
		'status', $installer->getTable('nostress_gpwebpay/transactions_status'), 'status',
		Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
	->setComment('Nostress Gpwebpay Transactions Status State Table');
$installer->getConnection()->createTable($table);

$installer->getConnection()->insertArray(
	$installer->getTable('nostress_gpwebpay/transactions_status_state'),
	array('status', 'state', 'is_default'),
	$states
);

/**
 * Create table 'nostress_gpwebpay/transactions_status_label'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/transactions_status_label'))
	->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
		'nullable'  => false,
		'primary'   => true,
		), 'Status')
	->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		), 'Store Id')
	->addColumn('label', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(
		'nullable'  => false,
		), 'Label')
	->addIndex($installer->getIdxName('nostress_gpwebpay/transactions_status_label', array('store_id')),
		array('store_id'))
	->addForeignKey($installer->getFkName('nostress_gpwebpay/transactions_status_label', 'status', 'nostress_gpwebpay/transactions_status', 'status'),
		'status', $installer->getTable('nostress_gpwebpay/transactions_status'), 'status',
		Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
	->addForeignKey($installer->getFkName('nostress_gpwebpay/transactions_status_label', 'store_id', 'core/store', 'store_id'),
		'store_id', $installer->getTable('core/store'), 'store_id',
		Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
	->setComment('Sales Order Status Label Table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'nostress_gpwebpay/nostress_gpwebpay'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/nostress_gpwebpay'))
	->addColumn('verify_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		), 'Verify Id')
	->addColumn('time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Time')
	->addColumn('string', Varien_Db_Ddl_Table::TYPE_TEXT, 200, array(
		'nullable' => false
		), 'String')
	->addColumn('digest', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable' => false
		), 'Digest')
	->addColumn('result', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
		), 'Result')
	->addIndex($installer->getIdxName('nostress_gpwebpay/nostress_gpwebpay', array('verify_id')),
		array('verify_id'))
	->setComment('Nostress Gpwebpay Table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'nostress_gpwebpay/nostress_gpwebpay_log'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('nostress_gpwebpay/nostress_gpwebpay_log'))
	->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		), 'Log Id')
	->addColumn('time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Time')
	->addColumn('prcode', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		), 'Primary Code')
	->addColumn('srcode', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		), 'Secondary Code')
	->addColumn('orderid', Varien_Db_Ddl_Table::TYPE_TEXT, 250, array(
		'nullable' => false
		), 'Order Id')
	->addColumn('function', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
		'nullable' => false
		), 'Name of the calling function')
	->addColumn('datasent', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable' => false
		), 'Sent Data')
	->addColumn('url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable' => false
		), 'Called Url')
	->addColumn('digest', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable' => false
		), 'Digest')
	->addColumn('return', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		), 'Return')
	->addColumn('params', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable' => false
		), 'Parameters')
	->addIndex($installer->getIdxName('nostress_gpwebpay/nostress_gpwebpay_log', array('log_id')),
		array('log_id'))
	->setComment('Nostress Gpwebpay Log Table');
$installer->getConnection()->createTable($table);

/**
 * Install eav entity types to the eav/entity_type table
 */
$installer->addEntityType('transactions', array(
    'entity_model'          => 'nostress_gpwebpay/transactions',
    'table'                 => 'nostress_gpwebpay/transactions',
    'increment_model'       => 'eav/entity_increment_numeric',
    'increment_per_store'   => true
));

$installer->endSetup();