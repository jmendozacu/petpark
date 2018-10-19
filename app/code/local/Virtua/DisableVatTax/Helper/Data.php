<?php

/**
 * Class Virtua_DisableVatTax_Helper_Data
 */
class Virtua_DisableVatTax_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Regex patterns of vat id for EU countries.
     * @var array
     */
    protected static $patterns = [
        'AT' => 'U[A-Z\d]{8}',                           # Austria
        'BE' => '(0\d{9}|\d{10})',                       # Belgium
        'BG' => '\d{9,10}',                              # Bulgaria
        'CY' => '\d{8}[A-Z]',                            # Cyprus
        'CZ' => '\d{8,10}',                              # Czech Republic
        'DE' => '\d{9}',                                 # Germany
        'DK' => '(\d{2} ?){3}\d{2}',                     # Denmark
        'EE' => '\d{9}',                                 # Estonia
        'EL' => '\d{9}',                                 # Greece
        'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}', # Spain
        'FI' => '\d{8}',                                 # Finland
        'FR' => '([A-Z]{2}|\d{2})\d{9}',                 # France
        'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',             # United Kingdom
        'HR' => '\d{11}',                                # Croatia
        'HU' => '\d{8}',                                 # Hungary
        'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',                 # Ireland
        'IT' => '\d{11}',                                # Italy
        'LT' => '(\d{9}|\d{12})',                        # Lithuania
        'LU' => '\d{8}',                                 # Luxembourg
        'LV' => '\d{11}',                                # Latvia
        'MT' => '\d{8}',                                 # Malta
        'NL' => '\d{9}B\d{2}',                           # Netherlands
        'PL' => '\d{10}',                                # Poland
        'PT' => '\d{9}',                                 # Portugal
        'RO' => '\d{2,10}',                              # Romania
        'SE' => '\d{12}',                                # Sweden
        'SI' => '\d{8}',                                 # Slovenia
        'SK' => '\d{10}'                                 # Slovakia
    ];

    /**
     * Check if customer must pay tax
     *
     * @return  bool
    */
    public function shouldDisableVatTax()
    {
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = $customerSession = Mage::getSingleton('customer/session');;

        if ($customerSession->getData('shouldDisableVatTax') !== null && !Mage::app()->getRequest()->isAjax()) {
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
        $vatNumber = $this->isVatNumberValid($vatNumber, $countryCode);
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

    /**
     * Checks is customer has a valid vat id. Using European Commission VAT validation.
     * @param string $vatNumber
     * @param string $countryCode
     * @return bool
     */
    public function isVatNumberValid($vatNumber, $countryCode)
    {
        $patterns = self::$patterns;
        $isValid = false;

        if (strpos($vatNumber, $countryCode) === 0) {
            $vatNumber = substr($vatNumber, strlen($countryCode));
            if (preg_match('/^'.$patterns[$countryCode].'$/', $vatNumber) > 0) {
                $viesClient = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
                $isValid = $viesClient->checkVat(['countryCode' => $countryCode, 'vatNumber' => $vatNumber])->valid;
            }
        }

        if ($isValid) {
            return true;
        } else {
            return false;
        }
    }
}