<?php

/**
 * Mage_Customer_AddressController
 */
require_once (Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AddressController.php');

/**
 * Class Virtua_DisableVatTax_AddressController
 */
class Virtua_DisableVatTax_AddressController extends Mage_Customer_AddressController
{
    /**
     * @return Mage_Core_Controller_Varien_Action
     */
    public function formPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        // Save data
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
                $helper = Mage::helper('virtua_disablevattax');
                $currentVatNumber = $customer->getDefaultBillingAddress()->getVatId();
                $newVatNumber = $addressData['vat_id'];

                if ($currentVatNumber != $newVatNumber) {
                    $vatNumberValidation = $helper->isVatNumberValid($newVatNumber, $addressData['country_id']);
                    $customer->setIsVatIdValid($vatNumberValidation)->save();

                    if ($vatNumberValidation) {
                        if ($helper->isDomesticCountry($addressData['country_id'])) {
                            $this
                                ->_getSession()
                                ->addSuccess($this->__('Your VAT ID was successfully validated. You will be charged tax.'));
                        } else {
                            $this
                                ->_getSession()
                                ->addSuccess('Your VAT ID was successfully validated. You will not be charged tax.');
                        }
                    } else {
                        $this
                            ->_getSession()
                            ->addError($this->__('The VAT ID entered ('.$newVatNumber.') is not a valid VAT ID. You will be charged tax.'));
                    }
                }

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
                } else {
                    $this->_getSession()->setAddressFormData($this->getRequest()->getPost());
                    foreach ($errors as $errorMessage) {
                        $this->_getSession()->addError($errorMessage);
                    }
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
}