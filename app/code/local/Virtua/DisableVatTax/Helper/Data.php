<?php

class Virtua_DisableVatTax_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Check if customer must pay tax
     *
     * @return  bool
    */
    public function shouldDisableVatTax()
    {
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        Zend_Debug::dump($customerSession->getData('shouldDisableVatTax'));
        if ($customerSession->getData('shouldDisableVatTax') !== null) {
            return $customerSession->getData('shouldDisableVatTax');
        }

        if (!$customerSession->isLoggedIn()) {
            $customerSession->setData('shouldDisableVatTax', false);
            return false;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $customerSession->getCustomer();

        /** @var string $countryCode */
        $countryCode = "";
        if ($customer->getDefaultBillingAddress()) {
            $countryCode = $customer->getDefaultBillingAddress()->getCountry();
        }

        $defaultCountry = Mage::getStoreConfig('general/country/default');
        $isCustomerOutsideDefaultCountry = $countryCode !== $defaultCountry;
        $vatNumber = $customer->getData('taxvat');
        $euCountries = $this->getEUCountries();
        if (!$euCountries) {
            $customerSession->setData('shouldDisableVatTax', false);
            return false;
        }

        $isEUCustomer = in_array($countryCode, $euCountries);
        if ($vatNumber && $isEUCustomer && $isCustomerOutsideDefaultCountry) {
            $customerSession->setData('shouldDisableVatTax', true);
            return true;
        }

        $customerSession->setData('shouldDisableVatTax', false);
        return false;
    }

    /**
     * Get EU countries from config as array
     *
     * @return array
    */
    public function getEUCountries()
    {
        return explode(",", Mage::getStoreConfig('general/country/eu_countries'));
    }
}