<?php

class Virtua_DisableVatTax_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return  bool
    */
    public function shouldDisableVatTax()
    {
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        if(!$customerSession->isLoggedIn()) return false;

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $customerSession->getCustomer();

        /** @var string $countryCode */
        $countryCode = $customer->getDefaultBillingAddress()->getCountry();

        $defaultCountry = Mage::getStoreConfig('general/country/default');
        $isCustomerOutsideDefaultCountry = $countryCode !== $defaultCountry;
        $vatNumber = $customer->getData('taxvat');
        $euCountries = $this->getEUCountries();
        $isEUCustomer = in_array($countryCode, $euCountries);
        if ($vatNumber && $isEUCustomer && $isCustomerOutsideDefaultCountry) {
            return true;
        }

        return false;
    }

    /**
     * @return array
    */
    public function getEUCountries()
    {
        return explode(",", Mage::getStoreConfig('general/country/eu_countries'));
    }
}