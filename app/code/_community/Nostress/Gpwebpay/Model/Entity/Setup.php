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
 * Setup Model Entity of Nostress Gpwebpay Module
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Entity_Setup extends Mage_Eav_Model_Entity_Setup
{
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
        /*return array(
            'order' => array(
                'entity_model'      => 'sales/order',
                'table'=>'sales/order',
                'increment_model'=>'eav/entity_increment_numeric',
                'increment_per_store'=>true,
                'backend_prefix'=>'sales_entity/order_attribute_backend',
                'attributes' => array(
                    'entity_id' => array(
                        'type'=>'static',
                        'backend'=>'sales_entity/order_attribute_backend_parent'
                    ),
                    'store_id'  => array('type'=>'static'),
                    'store_name' => array('type'=>'varchar'),
                    'remote_ip' => array(),

                    'status'    => array('type'=>'varchar'),
                    'state'     => array('type'=>'varchar'),
                    'hold_before_status' => array('type'=>'varchar'),
                    'hold_before_state'  => array('type'=>'varchar'),

                    'relation_parent_id'        => array('type'=>'varchar'),
                    'relation_parent_real_id'   => array('type'=>'varchar'),
                    'relation_child_id'         => array('type'=>'varchar'),
                    'relation_child_real_id'    => array('type'=>'varchar'),
                    'original_increment_id'     => array('type'=>'varchar'),
                    'edit_increment'            => array('type'=>'int'),
                ),
            ),
            'order_status_history' => array(
                'entity_model'      => 'sales/order_status_history',
                'table'=>'sales/order_entity',
                'attributes' => array(
                    'parent_id' => array(
                        'type'=>'static',
                        'backend'=>'sales_entity/order_attribute_backend_child'
                    ),
                    'status'    => array('type'=>'varchar'),
                    'comment'   => array('type'=>'text'),
                    'is_customer_notified' => array('type'=>'int'),
                ),
            ),
        );*/
    }
}
