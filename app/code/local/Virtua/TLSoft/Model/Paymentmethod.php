<?php
/**
 * @category  TlSoft
 * @package   Virtua_TlSoft
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_TLSoft_Model_Paymentmethod
 */
class Virtua_TLSoft_Model_Paymentmethod extends TLSoft_BarionPayment_Model_Paymentmethod
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


        if (Mage::getSingleton('core/session')->getBarionToken() != null) {
            $recurrenceId = Mage::getSingleton('core/session')->getBarionToken();
            $initiateRecurrence = false;
        } else {
            $recurrenceId = $this->prepareToken();
            $initiateRecurrence = true;
        }

        $header = [
            'POSKey'           => $helper->getShopId($storeid),
            'PaymentType'      => 'Immediate',
            'PaymentWindow'    => '00:30:00',
            'GuestCheckOut'    => true,
            'FundingSources'   => ['All'],
            'PaymentRequestId' => $lastorderid,
            'InitiateRecurrence' => $initiateRecurrence,
            'RecurrenceId' => $recurrenceId,
            'RedirectUrl'      => Mage::getBaseUrl().'tlbarion/redirection/respond/',
            'currency'         => $currency,
            'locale'           => 'sk-SK',
            'Transactions'     => [[
                'POSTransactionId' => $lastorderid,
                'Payee'            => $email,
                'Total'            => $ordertotal,
                'Items'            => $products
            ]]
        ];

        if ($initiateRecurrence == false) {
            unset($header['RedirectUrl']);
        }

        //sprawdzic czy jest token w sesji i czy chcemy go obslugiwac
        //wywalac index z tablicy
        //zapisywac do sesji token

        $paymentType = Mage::getStoreConfig('payment/tlbarion/virtua_tlsoft_paymenttype', Mage::app()->getStore());
        if ($paymentType == 'reservation') {
            $header['PaymentType'] = 'Reservation';
            $header['ReservationPeriod'] = Mage::getStoreConfig('payment/tlbarion/virtua_tlsoft_reservation_period', Mage::app()->getStore());
        }

        $products = '';

        $json = json_encode($header);

        $result = $helper->callCurl($json, $storeid);



        $resultarray = '';
        Mage::log(json_decode($result, true), null, 'TESTTOKENA2.log', true);
        if ($result != false) {
            if (Mage::getSingleton('core/session')->getBarionToken() == null) {
                Mage::getSingleton('core/session')->setBarionToken($recurrenceId);
            }
            $resultarray = json_decode($result, true);

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
     * @param $transaction
     * @return int
     */
    private function saveTrans($transaction)
    {
        $tablesave = $this->getTransModel()->setData($transaction)->save();
        return $tablesave->getEntityId();
    }

    public function prepareRefundData($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $helper = $this->otpHelper();
        $lastorderId = $order->getIncrementId();
        $storeid = $order->getStoreId();
        $transactionId = Mage::getModel('tlbarion/paymentmethod')->getTransModel()->loadByOrderId($orderId);
        $posTransactionId = $transaction->getEntityId();
        $transactionId = $transaction->getBariontransactionid();
        $paymentId = '970df57a6ccd485981ad55e8bb532d05';

        $header = [
            'POSKey'    => $this->getShopId($storeid),
            'PaymentId' =>  $real_orderid,
            'TransactionsToRefund' => [[
                'TransactionId'    => $transactionId,
                'POSTransactionId' => $posTransactionId,
                'AmountToRefund'   => $order->getGrandTotal()
            ]]
        ];

        $json = json_encode($header);

        $result = $helper->refundPayment($json, $storeid);

        $transid = $this->saveTrans([
            'real_orderid'   => $lastorderid,
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

        $result = $helper->callCurl($json, $storeid);
        $resultarray = '';

        if ($result != false) {
            $resultarray = json_decode($result, true);
            if (array_key_exists('PaymentId', $resultarray)) {
                $this->updateTrans(
                    [
                        'bariontransactionid' => $resultarray['Transactions'][0]['TransactionId']
                    ],
                    $transid
                );
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

    public function prepareToken()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        return 'PETPARK-XMLP-TOKEN-BARION-'.$customerId;
    }
}
