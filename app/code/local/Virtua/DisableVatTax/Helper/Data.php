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
                $vatNumberValidation = 2;
            } elseif ($this->isDomesticCountry($customer->getDefaultShippingAddress()->getCountry())) {
                $vatNumberValidation = 3;
            }
        }

        $customer->setIsVatIdValid($vatNumberValidation)->save();
    }
}
