<?php
/**
 * @category  TlSoft
 * @package   Virtua_TlSoft
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * TLSoft_BarionPayments_RedirectionController
 */
require 'TLSoft/BarionPayment/controllers/RedirectionController.php';

/**
 * Class Virtua_TLSoft_RedirectionController
 */
class Virtua_TLSoft_RedirectionController extends TLSoft_BarionPayment_RedirectionController
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
                $this->_redirect('tlbarion/redirection/success', array('_secure'=>true));
            } elseif ($otpdata == 'fail') {
                $this->_redirect('tlbarion/redirection/cancel', array('_secure'=>true));
            } elseif ($otpdata == 'reserved') {
                $this->_redirect('tlbarion/redirection/success', array('_secure'=>true));
            } else {
                $this->_redirect('tlbarion/redirection/cancel', array('_secure'=>true));
            }
        } else {
            $this->_redirect('tlbarion/redirection/cancel', array('_secure'=>true));
        }
    }
}
