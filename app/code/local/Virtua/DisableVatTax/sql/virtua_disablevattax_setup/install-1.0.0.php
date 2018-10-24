<?php

/**
 * Adding new attribute to customer which holds result of vat number validation.
 */
$installer = $this;

$installer->startSetup();

$installer->addAttribute(
    'customer', 'is_vat_id_valid',
    [
        'label'   => 'Is vat id valid',
        'visible' => 1,
        'required'=> 0,
        'default' => 0,
    ]
);

$customers = Mage::getModel('customer/customer')->getCollection();

foreach ($customers as $customer) {
    $address = $customer->getDefaultBillingAddress();
    $countrycode = $address->getCountry();
    $vatnumber = $address->getVatId();
    if(!empty($vatnumber) && !empty($countrycode)) {
        $vatNumberValidation = Mage::helper('virtua_disablevattax')->isVatNumberValid($vatnumber, $countrycode);
        if ($vatNumberValidation) {
            $customer->setIsVatIdValid($vatNumberValidation)->save();
        }
    }
}

$installer->endSetup();