<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Helper_Data
 */
class Virtua_BarionPayment_Helper_Data extends TLSoft_BarionPayment_Helper_Data
{
    const TESTREFUND      = 'https://api.test.barion.com/v2/Payment/Refund';
    const REFUND          = 'https://api.barion.com/v2/Payment/Refund';
    const TESTRESERVATION = 'https://api.test.barion.com/v2/Payment/FinishReservation';
    const RESERVATION     = 'https://api.barion.com/v2/Payment/FinishReservation';

    /**
     * Handling results of transaction process.
     *
     * @param string $order
     * @param array $transaction
     *
     * @return string
     */
    public function processTransResult($order = '', $transaction = array())
    {
        $otppayment = Mage::getModel('tlbarion/paymentmethod');

        if (empty($order)) {
            $order = $this->getCurrentOrder();
        }

        $storeid = $order->getStoreId();

        if (is_array($transaction)) {
            $transaction = $otppayment->getTransModel()->loadByOrderId($order->getId());
        }

        $transid = $transaction->getEntityId();

        $real_orderid = $transaction->getRealOrderid();

        $params = '?POSKey='.$this->getShopId($storeid).'&TransactionId='.$transaction->getBariontransactionid();

        $result = $this->callCurl2($params, $storeid);

        $resultarray = array();

        $return = 'pending';
        if ($result != false) {
            $resultarray = json_decode($result, true);
            if ($resultarray['Status']=='Succeeded') {
                $return = 'success';
                $status = '02';
            } elseif ($resultarray['Status'] == 'Prepared'
                || $resultarray['Status'] == 'Started') {
                $return = 'pending';
                $status = '01';
            } elseif ($resultarray['Status'] == 'Failed'
                ||$resultarray['Status'] == 'Expired'
                ||$resultarray['Status'] == 'Canceled'
                ||$resultarray['Status'] == 'Rejected') {
                $return = 'fail';
                $status = '00';
            } elseif ($resultarray['Status'] == 'Reserved') {
                $return = 'reserved';
                $status = '01';
            }
        }
        if (!empty($resultarray['Errors'])) {
            $status = '00';
            $otppayment->saveTransHistory([
                'transaction_id' => $transid,
                'error_message'  => $resultarray['Errors']['Description'],
                'error_number'   => $resultarray['Errors']['ErrorCode']]);
            $return = 'fail';
        }
        $otppayment->updateTrans(['payment_status' => $status], $transid);

        return $return;
    }

    /**
     * Refund payment.
     *
     * @param int $orderId
     */
    public function refundPayment($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $storeid = $order->getStoreId();
        $lastorderId = $order->getIncrementId();
        $transaction = Mage::getModel('tlbarion/paymentmethod')->getTransModel()->loadByOrderId($orderId);
        $posTransactionId = $transaction->getEntityId();
        $transactionId = $transaction->getBariontransactionid();
        $paymentId = $transaction->getData('real_orderid');

        $header = [
            'POSKey'    => $this->getShopId($storeid),
            'PaymentId' =>  $paymentId,
            'TransactionsToRefund' => [[
                'TransactionId'    => $transactionId,
                'POSTransactionId' => $posTransactionId,
                'AmountToRefund'   => $order->getGrandTotal()
            ]]
        ];

        $json = json_encode($header);
        $result = $this->callBarionToRefund($json, $storeid);

        if ($result != false) {
            $resultarray = json_decode($result, true);
            if (empty($resultarray['Errors'])) {
                $this->refundInAdmin($order);
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                $transaction
                    ->setData('payment_status', '02')
                    ->save();
                Mage::getSingleton('adminhtml/session')->addSuccess('Payment has been successfully refunded.');
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong. Payment has not been refunded.');
            }
        }
    }

    /**
     * Finishing reserved payment.
     *
     * @param int $orderId
     */
    public function finishReservation($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $storeid = $order->getStoreId();
        $transaction = Mage::getModel('tlbarion/paymentmethod')->getTransModel()->loadByOrderId($orderId);
        $transactionId = $transaction->getBariontransactionid();
        $paymentId = $transaction->getData('real_orderid');

        $header = [
            'POSKey'    => $this->getShopId($storeid),
            'PaymentId' =>  $paymentId,
            'Transactions'      => [[
                'TransactionId' => $transactionId,
                'Total'         => $order->getGrandTotal()
            ]]
        ];

        $json = json_encode($header);
        $result = $this->callBarionToFinishReservation($json, $storeid);

        if ($result != false) {
            $resultarray = json_decode($result, true);
            if (empty($resultarray['Errors'])) {
                $this->processOrderSuccess($order, false);
                $transaction
                    ->setData('bariontransactionid', $this->getSucceededTransaction($resultarray['Transactions']))
                    ->setData('payment_status', '02')
                    ->setData('real_orderid', $resultarray['PaymentId'])
                    ->save();
                Mage::getSingleton('adminhtml/session')->addSuccess('You have successfully finished a reservation.');
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong. Reservation has not been finished.');
            }
        }
    }

    /**
     * Use barion api to refund payment
     *
     * @param $json
     * @param int $storeId
     *
     * @return bool|mixed
     */
    public function callBarionToRefund($json, $storeId)
    {
        if ($this->isTest($storeId) == 1) {
            $url = self::TESTREFUND;
        } else {
            $url = self::REFUND;
        }

        try {
            $options = $this->getCurlOptions($json, $url);
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            $err     = curl_errno($ch);

            if (!$err) {
                curl_close($ch);
                return $content;
            }
            Mage::log('Barion curl error: '.$err, null, 'barion-curl-refund-error.log', true);
            return false;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Use barion api to finish reservation
     *
     * @param $json
     * @param int $storeId
     *
     * @return bool|mixed
     */
    public function callBarionToFinishReservation($json, $storeId)
    {
        if ($this->isTest($storeId) == 1) {
            $url = self::TESTRESERVATION;
        } else {
            $url = self::RESERVATION;
        }

        try {
            $options = $this->getCurlOptions($json, $url);
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            $err = curl_errno($ch);
            if (!$err) {
                curl_close($ch);
                return $content;
            }
            Mage::log('Barion curl error: '.$err, null, 'barion-curl-reservation-error.log', true);
            return false;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Refund payment in admin panel
     *
     * @param Nostress_Gpwebpay_Model_Order $order
     *
     * @return bool
     */
    public function refundInAdmin($order)
    {
        if (!$order->getId()) {
            return false;
        }
        $data = [];
        $service = Mage::getModel('sales/service_order', $order);
        $creditmemo = $service->prepareCreditmemo($data);
        $creditmemo->setPaymentRefundDisallowed(true)->register();
        try {
            Mage::getModel('core/resource_transaction')
                ->addObject($creditmemo)
                ->addObject($order)
                ->save();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Preparing invoice and setting new status to order.
     *
     * @param Nostress_Gpwebpay_Model_Order $order
     *
     * @return bool
     */
    public function processOrderSuccess($order, $isItNotFinishingReservation = true)
    {
        try {
            $this->usePreparedTokenAsBarionToken();

            if ($order && $isItNotFinishingReservation) {
                $invoice = $order->prepareInvoice();
                $invoice->register()->capture();
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            }

            if ($order->getState() != Mage_Sales_Model_Order::STATE_PROCESSING) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
                $order->setStatus('processing');
                $order->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Prepares invoice and sets new status to reserved order.
     *
     * @param Nostress_Gpwebpay_Model_Order $order
     *
     * @return bool
     */
    public function processOrderReserved($order)
    {
        try {
            $this->usePreparedTokenAsBarionToken();

            if ($order) {
                $invoice = $order->prepareInvoice();
                $invoice->register()->capture();
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            }

            if ($order->getState() != Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
                $order->setStatus('payment_review');
                $order->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Gets Id of successfull transaction.
     *
     * @param array $transactions
     *
     * @return string
     */
    public function getSucceededTransaction($transactions)
    {
        $transaction = end($transactions);
        if (array_key_exists('TransactionId', $transaction)) {
            return $transaction['TransactionId'];
        }
    }

    /**
     * Prepares array for connect with api.
     *
     * @param $json
     * @param string $url
     *
     * @return array
     */
    protected function getCurlOptions($json, $url)
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL            => $url,
            CURLOPT_POST           => 1,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Content-Length: ' . strlen($json)]
        ];
    }

    /**
     * Use prepared token as barion token.
     */
    public function usePreparedTokenAsBarionToken()
    {
        if (!Mage::getSingleton('core/session')->getBarionToken()
            && Mage::getModel('tlbarion/paymentmethod')->isTokenPaymentEnabled()) {
            $preparedBarionToken = Mage::getSingleton('core/session')->getPreparedBarionToken();
            Mage::getSingleton('core/session')->setBarionToken($preparedBarionToken);
        }
    }
}
