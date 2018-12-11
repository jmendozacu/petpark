<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_DisableVatTax_Helper_Data
 */
class Virtua_DisableVatTax_Helper_Data extends Mage_Core_Helper_Abstract
{
    const PASSED_VAT_VALIDATION_RESULT = 1;
    const VAT_VALIDATION_RESULT_WHEN_BILLING_COUNTRY_IS_DOMESTIC = 2;
    const VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC = 3;

    /**
     * Regex patterns of vat id for EU countries.
     *
     * @var array
     */
    protected static $patterns = [
        'AT' => 'U[A-Z\d]{8}',                           // Austria
        'BE' => '(0\d{9}|\d{10})',                       // Belgium
        'BG' => '\d{9,10}',                              // Bulgaria
        'CY' => '\d{8}[A-Z]',                            // Cyprus
        'CZ' => '\d{8,10}',                              // Czech Republic
        'DE' => '\d{9}',                                 // Germany
        'DK' => '(\d{2} ?){3}\d{2}',                     // Denmark
        'EE' => '\d{9}',                                 // Estonia
        'EL' => '\d{9}',                                 // Greece
        'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}', // Spain
        'FI' => '\d{8}',                                 // Finland
        'FR' => '([A-Z]{2}|\d{2})\d{9}',                 // France
        'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',             // United Kingdom
        'HR' => '\d{11}',                                // Croatia
        'HU' => '\d{8}',                                 // Hungary
        'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',                 // Ireland
        'IT' => '\d{11}',                                // Italy
        'LT' => '(\d{9}|\d{12})',                        // Lithuania
        'LU' => '\d{8}',                                 // Luxembourg
        'LV' => '\d{11}',                                // Latvia
        'MT' => '\d{8}',                                 // Malta
        'NL' => '\d{9}B\d{2}',                           // Netherlands
        'PL' => '\d{10}',                                // Poland
        'PT' => '\d{9}',                                 // Portugal
        'RO' => '\d{2,10}',                              // Romania
        'SE' => '\d{12}',                                // Sweden
        'SI' => '\d{8}',                                 // Slovenia
        'SK' => '\d{10}'                                 // Slovakia
    ];

    /**
     * Soap connection with VIES.
     *
     * @var Zend_Soap_Client
     */
    protected $viesClient;

    /**
     * Virtua_DisableVatTax_Helper_Data constructor
     */
    public function __construct()
    {
        $this->viesClient = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
    }

    /**
     * Checks is customer has a valid vat id with European Commission VAT validation.
     *
     * @param string $vatNumber
     * @param string $countryCode
     *
     * @return bool
     */
    public function isVatNumberValid($vatNumber, $countryCode)
    {
        $isValid = false;

        if (strpos($vatNumber, $countryCode) === 0) {
            $vatNumber = substr($vatNumber, strlen($countryCode));
            if ($this->checkVatNumberPattern($vatNumber, $countryCode)) {
                $viesClient = $this->viesClient;
                $isValid = $viesClient
                    ->checkVat(['countryCode' => $countryCode, 'vatNumber' => $vatNumber])
                    ->valid;
            }
        }

        return $isValid;
    }

    /**
     * Checks is it domestic country.
     *
     * @param string $country
     *
     * @return bool
     */
    public function isDomesticCountry($country)
    {
        $domesticCountry = Mage::getStoreConfig('general/country/default');
        return $domesticCountry == $country;
    }

    /**
     * Checks is vat number pattern valid.
     *
     * @param string $vatNumber
     * @param string $countryCode
     *
     * @return bool
     */
    public function checkVatNumberPattern($vatNumber, $countryCode)
    {
        $patterns = self::$patterns;
        return preg_match('/^'.$patterns[$countryCode].'$/', $vatNumber) > 0;
    }

    /**
     * Checks have customer values been changed in form request.
     *
     * @param Dotdigitalgroup_Email_Model_Customer $customer
     * @param array $addressData
     *
     * @return bool
     */
    public function areValuesChanged($customer, $addressData)
    {
        $currentVatNumber = null;
        $currentCountry = null;

        if ($customer->getDefaultBillingAddress()) {
            $currentVatNumber = $customer->getDefaultBillingAddress()->getVatId();
            $currentCountry = $customer->getDefaultBillingAddress()->getCountry();
        }
        $newVatNumber = $addressData['vat_id'];
        $newCountry = $addressData['country_id'];

        return $currentVatNumber != $newVatNumber || $currentCountry != $newCountry;
    }

    /**
     * Save vat number validation results to customer attribute.
     *
     * @param Dotdigitalgroup_Email_Model_Customer $customer
     * @param string $vatNumber
     * @param string $countryId
     */
    public function saveValidationResultsToAttr($customer, $vatNumber, $countryId)
    {
        $vatNumberValidation = $this->isVatNumberValid($vatNumber, $countryId);

        if ($vatNumberValidation) {
            if ($this->isDomesticCountry($countryId)) {
                $vatNumberValidation = self::VAT_VALIDATION_RESULT_WHEN_BILLING_COUNTRY_IS_DOMESTIC;
            } elseif ($this->isDomesticCountry($customer->getDefaultShippingAddress()->getCountry())) {
                $vatNumberValidation = self::VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC;
            }
        }

        $customer->setIsVatIdValid($vatNumberValidation)->save();
    }


    public function isAddressIsDefaultBilling(Dotdigitalgroup_Email_Model_Customer $customer, int $addressId): bool
    {
        return $addressId == $customer->getDefaultBillingAddress()->getId();
    }

    public function isAddressIsDefaultShipping(Dotdigitalgroup_Email_Model_Customer $customer, int $addressId): bool
    {
        return $addressId == $customer->getDefaultShippingAddress()->getId();
    }

    public function isAddressIsBilling(int $addressId, bool $defaultBillingParam, Dotdigitalgroup_Email_Model_Customer $customer): bool
    {
        if ($defaultBillingParam) {
            return true;
        }

        if ($this->isAddressIsDefaultBilling($customer, $addressId)) {
            return true;
        }

        return false;
    }

    public function isAddressIsShipping(int $addressId, bool $defaultShippingParam, Dotdigitalgroup_Email_Model_Customer $customer): bool
    {
        if ($defaultShippingParam) {
            return true;
        }

        if ($this->isAddressIsDefaultShipping($customer, $addressId)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $addressData
     */
    public function setCustomerVatAttributes(bool $isItBillingAddress, Dotdigitalgroup_Email_Model_Customer $customer, $addressData)
    {
        if ($isItBillingAddress && $this->areValuesChanged($customer, $addressData)) {
            $this->saveValidationResultsToAttr(
                $customer,
                $addressData['vat_id'],
                $addressData['country_id']
            );
        }
    }

    public function manageAttributesAccordingToShipping(bool $isItBillingAddress, bool $isItShippingAddress, Dotdigitalgroup_Email_Model_Customer $customer): bool
    {
        $isCustomerOutsideDomestic = null;

        if ($this->canManageAttributesAccordingToShipping($isItBillingAddress, $isItShippingAddress, $customer)) {
            $hasCustomerChangedShippingOutsideDomestic = $this->hasCustomerChangedShippingOutsideDomestic(
                (bool)$customer->getIsShippingOutsideDomestic(),
                (string)$addressData['country_id'],
                (string)$customer->getDefaultShippingAddress()->getCountry()
            );
        } else {
            return false;
        }

        if ($hasCustomerChangedShippingOutsideDomestic) {
            $customer->setIsVatIdValid(self::PASSED_VAT_VALIDATION_RESULT);
            $customer->setIsShippingOutsideDomestic(true);
        } else {
            $customer->setIsVatIdValid(self::VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC);
            $customer->setIsShippingOutsideDomestic(false);
        }

        $customer->save();
        return true;
    }

    public function hasCustomerChangedShippingOutsideDomestic(bool $isShippingOutsideDomesticAttribute, string $addressFormCountryId, string $defaultShippingCountry): bool
    {
        return $isShippingOutsideDomesticAttribute == 0
            && !$this->isDomesticCountry($addressFormCountryId)
            && $this->isDomesticCountry($defaultShippingCountry);
    }

    public function canManageAttributesAccordingToShipping(bool $isItBillingAddress, bool $isItShippingAddress, Dotdigitalgroup_Email_Model_Customer $customer): bool
    {
        $isCustomerVatIdValid = null;
        $isCustomerOutsideDomestic = null;

        if (!$isItBillingAddress && $isItShippingAddress) {
            $isCustomerVatIdValid = $customer->getIsVatIdValid();
            return $isCustomerVatIdValid == self::PASSED_VAT_VALIDATION_RESULT
                || $isCustomerVatIdValid == self::VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC;
        }

        return false;
    }
}
