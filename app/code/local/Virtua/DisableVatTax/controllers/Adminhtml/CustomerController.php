<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Mage_Customer_CustomerController
 */
require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'CustomerController.php';

/**
 * Class Virtua_DisableVatTax_CustomerController
 */
class Virtua_DisableVatTax_Adminhtml_CustomerController extends Mage_Adminhtml_CustomerController
{
    /**
     * Rewrites saveAction from CustomerController
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            $redirectBack = $this->getRequest()->getParam('back', false);
            $this->_initCustomer('customer_id');

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::registry('current_customer');

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm->setEntity($customer)
                ->setFormCode('adminhtml_customer')
                ->ignoreInvisible(false)
            ;

            $formData = $customerForm->extractData($this->getRequest(), 'account');

            if (isset($formData['disable_auto_group_change'])) {
                $formData['disable_auto_group_change'] = empty($formData['disable_auto_group_change']) ? '0' : '1';
            }

            $errors = null;
            if ($customer->getId()&& !empty($data['account']['new_password'])
                && Mage::helper('customer')->getIsRequireAdminUserToChangeUserPassword()
            ) {
                if (isset($data['account']['current_password'])) {
                    $currentPassword = $data['account']['current_password'];
                } else {
                    $currentPassword = null;
                }
                unset($data['account']['current_password']);
                $errors = $this->_validateCurrentPassword($currentPassword);
            }

            if (!is_array($errors)) {
                $errors = $customerForm->validateData($formData);
            }

            if ($errors !== true) {
                foreach ($errors as $error) {
                    $this->_getSession()->addError($error);
                }
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id' => $customer->getId())));
                return;
            }

            $customerForm->compactData($formData);

            if (isset($data['address']['_template_'])) {
                unset($data['address']['_template_']);
            }

            $modifiedAddresses = array();
            if (!empty($data['address'])) {
                /** @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('adminhtml_customer_address')->ignoreInvisible(false);

                foreach (array_keys($data['address']) as $index) {
                    $address = $customer->getAddressItemById($index);
                    if (!$address) {
                        $address = Mage::getModel('customer/address');
                    }

                    $requestScope = sprintf('address/%s', $index);
                    $formData = $addressForm->setEntity($address)
                        ->extractData($this->getRequest(), $requestScope);
                    $isDefaultBilling = isset($data['account']['default_billing'])
                        && $data['account']['default_billing'] == $index;
                    $address->setIsDefaultBilling($isDefaultBilling);
                    $isDefaultShipping = isset($data['account']['default_shipping'])
                        && $data['account']['default_shipping'] == $index;
                    $address->setIsDefaultShipping($isDefaultShipping);

                    $errors = $addressForm->validateData($formData);
                    if ($errors !== true) {
                        foreach ($errors as $error) {
                            $this->_getSession()->addError($error);
                        }
                        $this->_getSession()->setCustomerData($data);
                        $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array(
                                'id' => $customer->getId())
                        ));
                        return;
                    }

                    $addressForm->compactData($formData);

                    $address->setPostIndex($index);

                    if ($address->getId()) {
                        $modifiedAddresses[] = $address->getId();
                    } else {
                        $customer->addAddress($address);
                    }
                }
            }

            if (isset($data['account']['default_billing'])) {
                $customer->setData('default_billing', $data['account']['default_billing']);
            }
            if (isset($data['account']['default_shipping'])) {
                $customer->setData('default_shipping', $data['account']['default_shipping']);
            }
            if (isset($data['account']['confirmation'])) {
                $customer->setData('confirmation', $data['account']['confirmation']);
            }

            foreach ($customer->getAddressesCollection() as $customerAddress) {
                if ($customerAddress->getId() && !in_array($customerAddress->getId(), $modifiedAddresses)) {
                    $customerAddress->setData('_deleted', true);
                }
            }

            if (Mage::getSingleton('admin/session')->isAllowed('customer/newsletter')
                && !$customer->getConfirmation()
            ) {
                $customer->setIsSubscribed(isset($data['subscription']));
            }

            if (isset($data['account']['sendemail_store_id'])) {
                $customer->setSendemailStoreId($data['account']['sendemail_store_id']);
            }

            $isNewCustomer = $customer->isObjectNew();
            try {
                $sendPassToEmail = false;
                if ($isNewCustomer) {
                    $customer->setPassword($data['account']['password']);
                    $customer->setForceConfirmed(true);
                    if ($customer->getPassword() == 'auto') {
                        $sendPassToEmail = true;
                        $customer->setPassword($customer->generatePassword());
                    }
                }

                Mage::dispatchEvent('adminhtml_customer_prepare_save', array(
                    'customer'  => $customer,
                    'request'   => $this->getRequest()
                ));

                $customer->save();

                $disableVatTaxHelper = Mage::helper('virtua_disablevattax');
                $disableVatTaxHelper->saveValidationResultsToAttr(
                    $customer,
                    $customer->getDefaultBillingAddress()->getVatId(),
                    $customer->getDefaultBillingAddress()->getCountryId()
                );

                $isCustomerVatIdValid = $customer->getIsVatIdValid();
                $defaultShippingCountry = $customer->getDefaultShippingAddress()->getCountry();

                if ($isCustomerVatIdValid == Virtua_DisableVatTax_Helper_Data::PASSED_VAT_VALIDATION_RESULT
                    || $isCustomerVatIdValid == Virtua_DisableVatTax_Helper_Data::VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC) {
                    if ($customer->getIsShippingOutsideDomestic() == 0 && !$disableVatTaxHelper->isDomesticCountry($defaultShippingCountry)) {
                        $customer->setIsVatIdValid(Virtua_DisableVatTax_Helper_Data::PASSED_VAT_VALIDATION_RESULT);
                        $customer->setIsShippingOutsideDomestic(1);
                    } elseif ($disableVatTaxHelper->isDomesticCountry($defaultShippingCountry)) {
                        $customer->setIsVatIdValid(Virtua_DisableVatTax_Helper_Data::VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC);
                        $customer->setIsShippingOutsideDomestic(0);
                    }
                    $customer->save();
                }

                $this->addSessionVatInfo(
                    $isCustomerVatIdValid,
                    $addressData['country_id'],
                    $defaultShippingCountry
                );

                if ($customer->getWebsiteId() && (isset($data['account']['sendemail']) || $sendPassToEmail)) {
                    $storeId = $customer->getSendemailStoreId();
                    if ($isNewCustomer) {
                        $customer->sendNewAccountEmail('registered', '', $storeId);
                    } elseif (!$customer->getConfirmation()) {
                        $customer->sendNewAccountEmail('confirmed', '', $storeId);
                    }
                }

                if (!empty($data['account']['new_password'])) {
                    $newPassword = $data['account']['new_password'];
                    if ($newPassword == 'auto') {
                        $newPassword = $customer->generatePassword();
                    }
                    $customer->changePassword($newPassword);
                    $customer->sendPasswordReminderEmail();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('The customer has been saved.')
                );
                Mage::dispatchEvent('adminhtml_customer_save_after', array(
                    'customer'  => $customer,
                    'request'   => $this->getRequest()
                ));

                if ($redirectBack) {
                    $this->_redirect('*/*/edit', array(
                        'id' => $customer->getId(),
                        '_current' => true
                    ));
                    return;
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id' => $customer->getId())));
                return;
            } catch (Exception $e) {
                $this->_getSession()->addException(
                    $e,
                    Mage::helper('adminhtml')->__('An error occurred while saving the customer.')
                );
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id'=>$customer->getId())));
                return;
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/customer'));
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
        $session = $this->_getSession();
        $customer = Mage::registry('current_customer');

        if ($vatNumberValidation == Virtua_DisableVatTax_Helper_Data::PASSED_VAT_VALIDATION_RESULT) {
            $session
                ->addSuccess($this->__('Entered VAT ID was successfully validated. Customer will not be charged tax.'));
        } elseif ($vatNumberValidation == Virtua_DisableVatTax_Helper_Data::VAT_VALIDATION_RESULT_WHEN_BILLING_COUNTRY_IS_DOMESTIC) {
            $session
                ->addSuccess($this->__('Entered VAT ID was successfully validated. Customer will be charged tax.'));
        } elseif ($vatNumberValidation == Virtua_DisableVatTax_Helper_Data::VAT_VALIDATION_RESULT_WHEN_SHIPPING_COUNTRY_IS_DOMESTIC) {
            $session
                ->addSuccess($this->__('Entered VAT ID was successfully validated, but shipping address is in domestic country. Customer will be charged tax.'));
            $customer->setIsShippingOutsideDomestic(0)->save();
        } else {
            $session
                ->addError($this->__('Entered VAT ID is not a valid VAT ID. Customer will be charged tax.'));
        }
    }
}
