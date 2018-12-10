<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * IWD_Opc_JsonController
 */
require_once Mage::getModuleDir('controllers', 'IWD_Opc') . DS . 'JsonController.php';

/**
 * Class Virtua_DisableVatTax_JsonController
 */
class Virtua_DisableVatTax_JsonController extends IWD_Opc_JsonController
{
    /**
     * Billing save action.
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('billing', array());

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                if (isset($data['create_account']) && $data['create_account'] == 1) {
                    $this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
                } else {
                    $this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
                    unset($data['customer_password']);
                    unset($data['confirm_password']);
                }
            } else {
                $this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
            }

            $this->checkNewslatter();
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }

            $totals_before = $this->_getSession()->getQuote()->getGrandTotal();
            $methods_before = Mage::helper('opc')->getAvailablePaymentMethods();
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['isVirtual'] = true;
                }

                $data = $this->getRequest()->getPost('billing', array());
                Mage::dispatchEvent('opc_saveGiftMessage', array(
                    'request' => $this->getRequest(),
                    'quote' => $this->getOnepage()->getQuote(),
                ));

                if (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['shipping'] = $this->_getShippingMethodsHtml();
                }

                $methods_after = Mage::helper('opc')->getAvailablePaymentMethods();
                $use_method = Mage::helper('opc')->checkUpdatedPaymentMethods($methods_before, $methods_after);

                if ($use_method != -1) {
                    if (empty($use_method)) {
                        $use_method = -1;
                    }
                    $result['payments'] = $this->_getPaymentMethodsHtml($use_method, true);
                    $result['reload_payments'] = true;
                }
                $totals_after = $this->_getSession()->getQuote()->getGrandTotal();

                if ($totals_before != $totals_after) {
                    $result['reload_totals'] = true;
                }

            } else {
                $responseData['error'] = true;
                $responseData['message'] = $result['message'];
            }

            $this->checkVatNumberForOnepageCheckout($this->getOnepage()->getQuote());

            $this->getResponse()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Shipping save action.
     */
    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        $responseData = array();

        $result = array();

        if ($this->getRequest()->isPost()) {
            $totals_before = $this->_getSession()->getQuote()->getGrandTotal();

            $data = $this->getRequest()->getPost('shipping', array());

            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

            if (isset($result['error'])) {
                $responseData['error'] = true;
                $responseData['message'] = $result['message'];
                $responseData['messageBlock'] = 'shipping';
            } else {
                Mage::dispatchEvent('opc_saveGiftMessage', array(
                    'request' => $this->getRequest(),
                    'quote' => $this->getOnepage()->getQuote(),
                ));

                $responseData['shipping'] = $this->_getShippingMethodsHtml();
                $totals_after = $this->_getSession()->getQuote()->getGrandTotal();

                if ($totals_before != $totals_after) {
                    $responseData['reload_totals'] = true;
                }
            }
        }

        $this->checkVatNumberForOnepageCheckout($this->getOnepage()->getQuote());

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
    }

    /**
     * Checks is newsletter subscribed.
     */
    private function checkNewslatter()
    {
        $data = $this->getRequest()->getParams();
        if (isset($data['is_subscribed']) && $data['is_subscribed'] == 1) {
            Mage::getSingleton('core/session')->setIsSubscribed(true);
        } else {
            Mage::getSingleton('core/session')->unsIsSubscribed();
        }
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     */
    public function checkVatNumberForOnepageCheckout($quote)
    {
        $checkoutVatId = $quote->getBillingAddress()->getData('vat_id');
        $checkoutCountryId = $quote->getBillingAddress()->getData('country_id');
        $disableVatTaxHelper = Mage::helper('virtua_disablevattax');
        $vatNumberValidation = $disableVatTaxHelper->isVatNumberValid($checkoutVatId, $checkoutCountryId);

        if ($vatNumberValidation) {
            if ($disableVatTaxHelper->isDomesticCountry($checkoutCountryId)
            || $disableVatTaxHelper->isDomesticCountry($this->getOnepage()->getQuote()->getShippingAddress()->getData('country_id'))) {
                $vatNumberValidation = 0;
            }
        }

        Mage::getSingleton('core/session')->setIsCheckoutVatIdValid($vatNumberValidation);
    }

    public function getQuoteTaxAction()
    {
        $quote = $this->getOnepage()->getQuote();
        $taxAmount = $quote->getShippingAddress()->getTaxAmount();

        if ($taxAmount > 0) {
            $taxAmount = ($taxAmount/$quote->getSubtotal())*100;
        }

        $taxAmount = floor($taxAmount);
        $this->getResponse()->setBody($taxAmount);
    }

    public function checkIsDefaultAddressUsedAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $quote = $this->getOnepage()->getQuote();
        $response = false;

        $defaultBillingAddressId = $customer->getDefaultBillingAddress()->getId();
        $defaultShippingAddressId = $customer->getDefaultShippingAddress()->getId();

        $checkoutBillingAddressId = $quote->getBillingAddress()->getCustomerAddressId();
        $checkoutShippingAddressId = $quote->getShippingAddress()->getCustomerAddressId();

        if ($defaultBillingAddressId == $checkoutBillingAddressId
            && $defaultShippingAddressId == $checkoutShippingAddressId) {
            $response = true;
        }

        $this->getResponse()->setBody($response);
    }
}
