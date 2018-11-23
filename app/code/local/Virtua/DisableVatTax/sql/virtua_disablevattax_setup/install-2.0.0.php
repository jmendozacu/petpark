<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

$installer = $this;

$installer->startSetup();

$attr = Mage::getResourceModel('catalog/eav_attribute')
    ->loadByCode('customer', 'is_vat_id_valid')
    ->getId();

if (!$attr) {
    $installer->addAttribute(
        'customer',
        'is_vat_id_valid',
        [
            'label' => 'Is vat id valid',
            'visible' => 1,
            'required' => 0,
            'default' => 0,
        ]
    );
    $installer->addAttribute(
        'customer',
        'should_disable_vat_tax',
        [
            'label' => 'Should disable vat tax',
            'visible' => 1,
            'required' => 0,
            'default' => 0,
        ]
    );
}

$customers = Mage::getModel('customer/customer')->getCollection();
$helper = Mage::helper('virtua_disablevattax');

/**
 * Checks for every customer is vat number is valid,
 * sets 'is_vat_id_valid' attribute value.
 */
foreach ($customers as $customer) {
    $address = $customer->getDefaultBillingAddress();
    if ($address) {
        $countrycode = $address->getCountry();
        $vatnumber = $address->getVatId();
        if (!empty($vatnumber) && !empty($countrycode)) {
            $vatNumberValidation = $helper->isVatNumberValid($vatnumber, $countrycode);
            if ($vatNumberValidation) {
                $customer
                    ->setIsVatIdValid($vatNumberValidation)
                    ->save();
            }
        }
    }
}

$installer->endSetup();
