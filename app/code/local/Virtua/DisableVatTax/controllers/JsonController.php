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
            $this->manageCustomerCheckoutMethod((bool)Mage::getSingleton('customer/session')->isLoggedIn(), $data);

            $this->checkNewslatter();
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
            $this->setPreparedEmailToRequest($data);

            $totals_before = $this->_getSession()->getQuote()->getGrandTotal();
            $methods_before = Mage::helper('opc')->getAvailablePaymentMethods();
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                $this->setIsVirtualKeyToResult($result);

                $data = $this->getRequest()->getPost('billing', array());
                Mage::dispatchEvent('opc_saveGiftMessage', array(
                    'request' => $this->getRequest(),
                    'quote' => $this->getOnepage()->getQuote(),
                ));

                $result['shipping'] = $this->setShippingToResult($data);
                $methods_after = Mage::helper('opc')->getAvailablePaymentMethods();
                $this->setPreparedPaymentDataToResult(Mage::helper('opc')->checkUpdatedPaymentMethods($methods_before, $methods_after), $result);
                $totals_after = $this->_getSession()->getQuote()->getGrandTotal();
                $this->setReloadTotalsToResult($totals_before, $totals_after, $result);
            } else {
                $this->setErrorToResponse($responseData, $result);
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

    /**
     * Prepares tax in percent.
     */
    public function getQuoteTaxAction()
    {
        $quote = $this->getOnepage()->getQuote();
        $taxAmount = $quote->getShippingAddress()->getTaxAmount();

        if ($taxAmount > 0) {
            $taxAmount = ($taxAmount/$quote->getSubtotal())*100;
        }

        $taxAmount = round($taxAmount);
        $this->getResponse()->setBody($taxAmount);
    }

    /**
     * Checks is address used on checkout is the same as default customer address.
     */
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

    /**
     * @return bool
     */
    public function isQuoteVirtual()
    {
        return (bool)$this->getOnepage()->getQuote()->isVirtual();
    }

    /**
     * @param array $result
     */
    public function setIsVirtualKeyToResult($result)
    {
        if ($this->isQuoteVirtual()) {
            $result['isVirtual'] = true;
        }
    }

    /**
     * @param bool $isLoggedIn
     * @param array $request
     */
    public function manageCustomerCheckoutMethod($isLoggedIn, $request)
    {
        if (!$isLoggedIn) {
            $this->manageLoggedOutCustomerRequest($request);
        } else {
            $this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
        }
    }

    /**
     * @param array $request
     */
    public function manageLoggedOutCustomerRequest($request)
    {
        if (isset($request['create_account']) && $request['create_account'] == 1) {
            $this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
        } else {
            $this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            unset($request['customer_password']);
            unset($request['confirm_password']);
        }
    }

    /**
     * @param array $request
     */
    public function setPreparedEmailToRequest($request)
    {
        if (isset($request['email'])) {
            $request['email'] = trim($request['email']);
        }
    }

    /**
     * @param string|int $updatedPaymentMethod
     * @param array $result
     */
    public function setPreparedPaymentDataToResult($updatedPaymentMethod, $result)
    {
        if ($updatedPaymentMethod != -1) {
            if (empty($updatedPaymentMethod)) {
                $updatedPaymentMethod = -1;
            }
            $result['payments'] = $this->_getPaymentMethodsHtml($updatedPaymentMethod, true);
            $result['reload_payments'] = true;
        }
    }

    /**
     * @param array $response
     * @param array $result
     */
    public function setErrorToResponse($response, $result)
    {
        $response['error'] = true;
        $response['message'] = $result['message'];
    }

    /**
     * @param float $totalsBefore
     * @param float $totalsAfter
     * @param array $result
     */
    public function setReloadTotalsToResult($totalsBefore, $totalsAfter, $result)
    {
        if ($this->shouldReloadTotals($totalsBefore, $totalsAfter)) {
            $result['reload_totals'] = true;
        }
    }

    /**
     * @param float $totalsBefore
     * @param float $totalsAfter
     *
     * @return bool
     */
    public function shouldReloadTotals($totalsBefore, $totalsAfter)
    {
        return $totalsBefore != $totalsAfter;
    }

    /**
     * @param array $request
     *
     * @return string|null
     */
    public function setShippingToResult($request)
    {
        if (isset($request['use_for_shipping']) && $request['use_for_shipping'] == 1) {
            return $this->_getShippingMethodsHtml();
        }
    }
}
