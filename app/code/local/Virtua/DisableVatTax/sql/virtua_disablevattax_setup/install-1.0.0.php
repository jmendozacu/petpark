<?php
/**
 * Installing customer attribute for vat number validation'
 *
 * PHP version 7.1.21
 *
 * @category  Controller
 * @package   Virtua\DisableVatTax\controllers\AddressController
 * @author    Maciej Skalny <m.skalny@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Adding new attribute to customer which holds result of vat number validation.
 */
$installer = new Mage_Customer_Model_Entity_Setup();

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
}

$customers = Mage::getModel('customer/customer')->getCollection();

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
            $vatNumberValidation = Mage::helper('virtua_disablevattax')->isVatNumberValid($vatnumber, $countrycode);
            if ($vatNumberValidation) {
                $customer
                    ->setIsVatIdValid($vatNumberValidation)
                    ->save();
            }
        }
    }
}

$installer->endSetup();
