<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Mage_Customer_AddressController
 */
require_once Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AddressController.php';

/**
 * Class Virtua_DisableVatTax_AddressController
 */
class Virtua_DisableVatTax_AddressController extends Mage_Customer_AddressController
{
    /**
     * Rewrites formPostAction from AddressController
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function formPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }

        if ($this->getRequest()->isPost()) {
            $customer = $this->_getSession()->getCustomer();
            /* @var $address Mage_Customer_Model_Address */
            $address  = Mage::getModel('customer/address');
            $addressId = $this->getRequest()->getParam('id');
            if ($addressId) {
                $existsAddress = $customer->getAddressById($addressId);
                if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                    $address->setId($existsAddress->getId());
                }
            }

            $errors = array();

            /* @var $addressForm Mage_Customer_Model_Form */
            $addressForm = Mage::getModel('customer/form');
            $addressForm->setFormCode('customer_address_edit')
                ->setEntity($address);
            $addressData    = $addressForm->extractData($this->getRequest());
            $addressErrors  = $addressForm->validateData($addressData);
            if ($addressErrors !== true) {
                $errors = $addressErrors;
            }

            try {
                $disableVatTaxHelper = Mage::helper('virtua_disablevattax');

                $isItBillingAddress = $disableVatTaxHelper->isAddressIsBilling(
                    $addressId,
                    (bool)$this->getRequest()->getParam('default_billing'),
                    $customer
                );

                $isItShippingAddress = $disableVatTaxHelper->isAddressIsShipping(
                    $addressId,
                    (bool)$this->getRequest()->getParam('default_shipping'),
                    $customer
                );

                $disableVatTaxHelper->setCustomerVatAttributes($isItBillingAddress, $customer, $addressData);
                $disableVatTaxHelper->manageAttributesAccordingToShipping($isItBillingAddress, $isItShippingAddress, $customer);

                $this->addSessionVatInfo(
                    $customer->getIsVatIdValid(),
                    $addressData['country_id'],
                    $customer->getDefaultShippingAddress()->getCountry()
                );

                $addressForm->compactData($addressData);
                $address->setCustomerId($customer->getId())
                    ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                    ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
                $addressErrors = $address->validate();
                if ($addressErrors !== true) {
                    $errors = array_merge($errors, $addressErrors);
                }

                if (count($errors) === 0) {
                    $address->save();
                    $this->_getSession()->addSuccess($this->__('The address has been saved.'));
                    $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
                    return;
                }
                $this->_getSession()->setAddressFormData($this->getRequest()->getPost());
                foreach ($errors as $errorMessage) {
                    $this->_getSession()->addError($errorMessage);
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
                    ->addException($e, $e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save address.'));
            }
        }
        return $this->_redirectError(Mage::getUrl('*/*/edit', array('id' => $address->getId())));
    }

    /**
     * Adding info about vat validation to session.
     *
     * @param bool $vatNumberValidation
     * @param string $countryId
     * @param string $defaultShippingCountry
     */
    public function addSessionVatInfo($vatNumberValidation, $countryId, $defaultShippingCountry)
    {
        $helper = Mage::helper('virtua_disablevattax');
        $session = $this->_getSession();
        $customer = $session->getCustomer();

        if ($vatNumberValidation == 1) {
            $session
                ->addSuccess($this->__('Your VAT ID was successfully validated. You will not be charged tax.'));
        } elseif ($vatNumberValidation == 2) {
            $session
                ->addSuccess($this->__('Your VAT ID was successfully validated. You will be charged tax.'));
        } elseif ($vatNumberValidation == 3) {
            $session
                ->addSuccess($this->__('Your VAT ID was successfully validated, but your shipping address is in domestic country. You will be charged tax.'));
            $customer->setIsShippingOutsideDomestic(0)->save();
        } else {
            $session
                ->addError($this->__('Entered VAT ID is not a valid VAT ID. You will be charged tax.'));
        }
    }
}
