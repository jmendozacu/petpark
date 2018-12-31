<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

$installer = $this;

$installer->startSetup();

$barionTokenAttribute = Mage::getResourceModel('catalog/eav_attribute')
    ->loadByCode('customer', 'barion_token')
    ->getId();

$preparedBarionTokenAttribute = Mage::getResourceModel('catalog/eav_attribute')
    ->loadByCode('customer', 'prepared_barion_token')
    ->getId();

if (!$barionTokenAttribute && !$preparedBarionTokenAttribute) {
    $installer->addAttribute(
        'customer',
        'barion_token',
        [
            'label' => 'Barion token',
            'visible' => 1,
            'required' => 0,
            'default' => 0,
        ]
    );
    $installer->addAttribute(
        'customer',
        'prepared_barion_token',
        [
            'label' => 'Prepared Barion token',
            'visible' => 1,
            'required' => 0,
            'default' => 0,
        ]
    );
}

$installer->endSetup();
