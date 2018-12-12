<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

$installer = $this;
$installer->startSetup();
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$isVatIdValidAttributeId = Mage::getResourceModel('catalog/eav_attribute')
    ->loadByCode('customer', 'is_vat_id_valid')
    ->getId();

$isShippingOutsideDomesticAttributeId = Mage::getResourceModel('catalog/eav_attribute')
    ->loadByCode('customer', 'is_shipping_outside_domestic')
    ->getId();

if (!$isVatIdValidAttributeId) {
    $setup->addAttribute(
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

if (!$isShippingOutsideDomesticAttributeId) {
    $setup->addAttribute(
        'customer',
        'is_shipping_outside_domestic',
        [
            'label' => 'Is shipping outside domestic country',
            'visible' => 1,
            'required' => 0,
            'default' => 0,
        ]
    );
}

$customers = Mage::getModel('customer/customer')->getCollection();
$disableVatTaxHelper = Mage::helper('virtua_disablevattax');

/**
 * Checks for every customer is vat number is valid,
 * sets 'is_vat_id_valid' and 'is_shipping_outside_domestic' attribute value.
 */
foreach ($customers as $customer) {
    $billingAddress = $customer->getDefaultBillingAddress();
    if ($billingAddress) {
        $countryId = $billingAddress->getCountry();
        $vatNumber = $billingAaddress->getVatId();
        if (!empty($vatNumber) && !empty($countryId)) {
            $vatNumberValidation = $disableVatTaxHelper->saveValidationResultsToAttr($customer, $vatNumber, $countryId);
        }
        $customer->setIsShippingOutsideDomestic(
            $disableVatTaxHelper->isDomesticCountry(
                $customer->getDefaultShippingAddress()->getCountry()
            )
        );
    }
    $customer->save();
}

$installer->endSetup();
