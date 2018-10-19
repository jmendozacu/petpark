<?php

/**
 * Class Virtua_DisableVatTax_Block_VatNumber
 */
class Virtua_DisableVatTax_Block_VatNumber extends Mage_Core_Block_Template
{
    /**
     * Checks is vat number is valid.
     * @return bool
     */
    public function isVatNumberValid()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customerVatNumber = $customer->getData('taxvat');
        $customerCountryCode = $customer->getDefaultBillingAddress()->getCountry();
        $helper = Mage::helper('virtua_disablevattax');

        return $helper->isVatNumberValid($customerVatNumber, $customerCountryCode);
    }
}