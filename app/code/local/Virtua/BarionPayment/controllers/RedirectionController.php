<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * TLSoft_BarionPayments_RedirectionController
 */
require 'TLSoft/BarionPayment/controllers/RedirectionController.php';

/**
 * Class Virtua_BarionPayment_RedirectionController
 */
class Virtua_BarionPayment_RedirectionController extends TLSoft_BarionPayment_RedirectionController
{
    public function respondAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $otppayment = Mage::getModel('tlbarion/paymentmethod');
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        if ($order->getId()) {
            $otphelper = $otppayment->otpHelper();
            $otpdata=$otphelper->processTransResult();
            if ($otpdata == 'success') {
                $otphelper->processOrderSuccess($order);
                $this->barionRedirect('success');
            } elseif ($otpdata == 'fail') {
                $this->barionRedirect('cancel');
            } elseif ($otpdata == 'reserved') {
                $otphelper->processOrderReserved($order);
                $this->barionRedirect('success');
            } elseif ($otpdata == 'pending') {
                $this->barionRedirect('success');
            } else {
                $this->barionRedirect('cancel');
            }
        } else {
            $this->barionRedirect('cancel');
        }
    }

    /**
     * Redirect to url
     * @param string $redirect
     */
    public function barionRedirect($redirect)
    {
        if ($redirect == 'success' || $redirect == 'cancel') {
            $this->_redirect('tlbarion/redirection/' . $redirect, array('_secure' => true));
        }
    }

    public function removeTokenAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customer->setBarionToken(null)->save();
        $this->_redirect('checkout/onepage');
    }
}
