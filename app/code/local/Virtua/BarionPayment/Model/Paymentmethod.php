<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Model_Paymentmethod
 */
class Virtua_BarionPayment_Model_Paymentmethod extends TLSoft_BarionPayment_Model_Paymentmethod
{
    protected $_canAuthorize            = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;

    /**
     * @return bool|string
     */
    public function getOtpUrl()
    {
        $helper = $this->otpHelper();
        $order  = $helper->getCurrentOrder();
        if (!$order->getId()) {
            return false;
        }
        $storeid    = $order->getStoreId();
        $email      = $helper->getEmail($storeid);
        $session    = Mage::getSingleton('checkout/session');
        $currency   = $order->getOrderCurrencyCode();
        $ordertotal = $order->getGrandTotal();

        $locale = $helper->checkLocalCode();
        $lastorderid = $order->getIncrementId();

        $products = array();
        $items = $order->getAllVisibleItems();
        $i = 0;
        foreach ($items as $item) {
            $products[$i]['Name']        = $item->getName();
            $products[$i]['Description'] = $item->getName();
            $products[$i]['Quantity']    = $item->getQtyOrdered();
            $products[$i]['Unit']        = 'db';
            $products[$i]['UnitPrice']   = $item->getPriceInclTax();
            $products[$i]['ItemTotal']   = $item->getRowTotalInclTax();
            $i++;
        }
        $shipping = $order->getShippingInclTax();
        if ($shipping > 0) {
            $products[$i]['Name']        = $order->getShippingDescription();
            $products[$i]['Description'] = $order->getShippingDescription();
            $products[$i]['Quantity']    = 1;
            $products[$i]['Unit']        = 'db';
            $products[$i]['UnitPrice']   = $shipping;
            $products[$i]['ItemTotal']   = $shipping;
        }

        $header = [
            'POSKey'           => $helper->getShopId($storeid),
            'PaymentType'      => 'Immediate',
            'PaymentWindow'    => '00:30:00',
            'GuestCheckOut'    => true,
            'FundingSources'   => ['All'],
            'PaymentRequestId' => $lastorderid,
            'RedirectUrl'      => Mage::getUrl('tlbarion/redirection/respond/'),
            'currency'         => $currency,
            'locale'           => 'sk-SK',
            'Transactions'     => [[
                'POSTransactionId' => $lastorderid,
                'Payee'            => $email,
                'Total'            => $ordertotal,
                'Items'            => $products
            ]]
        ];

        if ($this->isTokenPaymentEnabled()) {
            if (Mage::getSingleton('core/session')->getBarionToken()) {
                $header['RecurrenceId'] = Mage::getSingleton('core/session')->getBarionToken();
                $header['InitiateRecurrence'] = false;
                unset($header['RedirectUrl']);
                $header['CallbackUrl'] = Mage::getUrl('tlbarion/redirection/respond/');
            } else {
                $header['RecurrenceId'] = $this->prepareToken();
                $header['InitiateRecurrence'] = true;
            }
        }

        $paymentType = Mage::getStoreConfig('payment/tlbarion/virtua_barionpayment_paymenttype', Mage::app()->getStore());
        if ($paymentType == 'reservation') {
            $header['PaymentType'] = 'Reservation';
            $reservationPeriod = Mage::getStoreConfig('payment/tlbarion/virtua_barionpayment_reservation_period', Mage::app()->getStore());
            if ($reservationPeriod) {
                $header['ReservationPeriod'] = $reservationPeriod;
            } else {
                $header['ReservationPeriod'] = '1.00:00:00';
            }
        }

        $products = '';
        $json = json_encode($header);
        $result = $helper->callCurl($json, $storeid);
        $resultarray = json_decode($result, true);

        if ($header['InitiateRecurrence'] == false && $this->areFundsInsufficient($resultarray)) {
            $header = $this->dontUseExsistingToken($header);
            $json = json_encode($header);
            $result = $helper->callCurl($json, $storeid);
            $resultarray = json_decode($result, true);
        }

        if ($result != false) {
            if (!Mage::getSingleton('core/session')->getBarionToken() && $this->isTokenPaymentEnabled()) {
                Mage::getSingleton('core/session')->setPreparedBarionToken($header['RecurrenceId']);
            }
            if (array_key_exists('PaymentId', $resultarray)) {
                $transid = $this->saveTrans([
                    'real_orderid'   => $resultarray['PaymentId'],
                    'order_id'       => $order->getId(),
                    'application_id' => $helper->getShopId($storeid),
                    'amount'         => $ordertotal,
                    'ccy'            => $currency,
                    'store_id'       => $storeid,
                    'payment_status' => '01',
                    'created_at'     => now()
                ]);

                $this->saveTransHistory([
                    'transaction_id' => $transid,
                    'created_at'     => now()
                ]);

                $this->updateTrans(
                    [
                        'bariontransactionid' => $resultarray['Transactions'][0]['TransactionId']
                    ],
                    $transid
                );

                if ($header['InitiateRecurrence'] == false) {
                    $url = Mage::getUrl('tlbarion/redirection/respond');
                    return $url;
                }
                $url = $helper->getRedirectUrl($storeid);
                return $url.'?id='.$resultarray['PaymentId'];
            }
            foreach ($resultarray['Errors'] as $error) {
                $this->saveTransHistory([
                    'created_at'     => now(),
                    'transaction_id' => $transid,
                    'error_message'  => $error['Description'],
                    'error_number'   => $error['ErrorCode']]);
            }
            return false;
        }
        return false;
    }

    /**
     * @return float
     */
    protected function getOrderTotal()
    {
        return $this->otpHelper()->getCurrentOrder()->getGrandTotal();
    }

    /**
     * @param array $transaction
     * @return int
     */
    private function saveTrans($transaction)
    {
        $tablesave = $this->getTransModel()->setData($transaction)->save();
        return $tablesave->getEntityId();
    }

    /**
     * @return string
     */
    public function prepareToken()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        $uniqueCode = mt_rand(100000, 999999);

        return 'PETPARK-XMLP-TOKEN-'.$uniqueCode.'-'.$customerId;
    }

    /**
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isTokenPaymentEnabled()
    {
        $isTokenEnabled = Mage::getStoreConfig('payment/tlbarion/virtua_barionpayment_token', Mage::app()->getStore());
        if ($isTokenEnabled && Mage::getSingleton('customer/session')->isLoggedIn()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function areFundsInsufficient($resultarray)
    {
        if (array_key_exists('Errors', $resultarray)) {
            foreach ($resultarray['Errors'] as $error) {
                if ($error['ErrorCode'] == 'InsufficientFunds') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $header
     * @return array
     */
    public function dontUseExsistingToken($header)
    {
        $header['InitiateRecurrence'] = true;
        $header['RedirectUrl'] = Mage::getUrl('tlbarion/redirection/respond/');
        unset($header['CallbackUrl']);

        return $header;
    }
}
