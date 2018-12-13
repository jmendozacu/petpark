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
    const PENDING_TRANSACTION_STATUS = '01';
    const SUCCESS_TRANSACTION_STATUS = '02';
    const FAILED_TRANSACTION_STATUS = '00';

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
        $otpPayment = Mage::getModel('tlbarion/paymentmethod');

        if (empty($order)) {
            $order = $this->getCurrentOrder();
        }

        $storeId = $order->getStoreId();

        if (is_array($transaction)) {
            $transaction = $otpPayment->getTransModel()->loadByOrderId($order->getId());
        }

        $transId             = $transaction->getEntityId();
        $params              = '?POSKey='.$this->getShopId($storeId).'&TransactionId='.$transaction->getBariontransactionid();
        $result              = $this->callCurl2($params, $storeId);
        $resultArray         = [];
        $transactionStatus   = 'pending';
        $transactionStatusId = self::PENDING_TRANSACTION_STATUS;

        if ($result != false) {
            $resultArray = json_decode($result, true);
            $barionTransactionStatus = $this->getBarionTransactionStatus($resultArray['Status'], $transactionStatus, $transactionStatusId);
            $transactionStatus = $barionTransactionStatus['transactionStatus'];
            $transactionStatusId = $barionTransactionStatus['transactionStatusId'];
        }

        if (!empty($resultArray['Errors'])) {
            $transactionStatusId = self::FAILED_TRANSACTION_STATUS;
            $otppayment->saveTransHistory([
                'transaction_id' => $transId,
                'error_message'  => $resultArray['Errors']['Description'],
                'error_number'   => $resultArray['Errors']['ErrorCode']]);
            $transactionStatus = 'fail';
        }
        $otpPayment->updateTrans(['payment_status' => $transactionStatusId], $transId);

        return $transactionStatus;
    }

    /**
     * Refund payment.
     *
     * @param int $orderId
     * @param float|null $total
     *
     * @return bool
     */
    public function refundPayment($orderId, $total = null)
    {
        $order            = Mage::getModel('sales/order')->load($orderId);
        $storeid          = $order->getStoreId();
        $lastorderId      = $order->getIncrementId();
        $transaction      = Mage::getModel('tlbarion/paymentmethod')->getTransModel()->loadByOrderId($orderId);
        $posTransactionId = $transaction->getEntityId();
        $transactionId    = $transaction->getBariontransactionid();
        $paymentId        = $transaction->getData('real_orderid');

        if (!$total) {
            $total = $order->getTotalPaid() - $order->getTotalRefunded();
            $total = bcdiv($total, 1, 2);
        }

        $header = [
            'POSKey'    => $this->getShopId($storeid),
            'PaymentId' =>  $paymentId,
            'TransactionsToRefund' => [[
                'TransactionId'    => $transactionId,
                'POSTransactionId' => $posTransactionId,
                'AmountToRefund'   => $total
            ]]
        ];

        $json   = json_encode($header);
        $result = $this->callBarionToRefund($json, $storeid);

        if ($result != false) {
            $resultArray = json_decode($result, true);
            return $this->proccessRefundResponse($resultArray, $transaction);
        }
    }

    /**
     * Finishing reserved payment.
     *
     * @param int $orderId
     * @param float|null $total
     * @param bool|null $isFinishedByInvoice
     * @param array|null $items
     *
     * @return bool
     */
    public function finishReservation($orderId, $total = null, $isFinishedByInvoice = null, $items = null)
    {
        $order         = Mage::getModel('sales/order')->load($orderId);
        $storeId       = $order->getStoreId();
        $transaction   = Mage::getModel('tlbarion/paymentmethod')->getTransModel()->loadByOrderId($orderId);
        $transactionId = $transaction->getBariontransactionid();
        $paymentId     = $transaction->getData('real_orderid');

        if (!$total) {
            $total = $order->getGrandTotal();
        }

        $header = [
            'POSKey'    => $this->getShopId($storeId),
            'PaymentId' =>  $paymentId,
            'Transactions'      => [[
                'TransactionId' => $transactionId,
                'Total'         => $total
            ]]
        ];

        if ($items) {
            $products = array();
            $i = 0;
            foreach ($items as $item) {
                $products[$i]['Name']        = $item->getName();
                $products[$i]['Description'] = $item->getName();
                $products[$i]['Quantity']    = $item->getQty();
                $products[$i]['Unit']        = 'db';
                $products[$i]['UnitPrice']   = $item->getPriceInclTax();
                $products[$i]['ItemTotal']   = $item->getRowTotalInclTax();
                $i++;
            }
            $header['Transactions'][0]['Items'] = $products;
        }

        $json   = json_encode($header);
        $result = $this->callBarionToFinishReservation($json, $storeId);

        if ($result != false) {
            $resultArray = json_decode($result, true);
            return $this->processFinishReservationResponse($order, (bool)$isFinishedByInvoice, $resultArray, $transaction);
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
            $ch      = curl_init();
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
            $ch      = curl_init();
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            $err     = curl_errno($ch);
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
     * Preparing invoice and setting new status to order.
     *
     * @param Nostress_Gpwebpay_Model_Order $order
     *
     * @return bool
     */
    public function processOrderSuccess($order)
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
            $order->setState(Mage_Sales_Model_Order::STATE_NEW);
            $order->setStatus('reservation');
            $order->save();
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
        /**
         * Count of transactions for payment with wallet will be 4,
         * but for payment with card it will be 3.
         */
        $transaction = $transactions[0];
        if (count($transactions) > 3 && end($transactions)['POSTransactionId'] != null) {
            $transaction = end($transactions);
        }

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
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if (!$customer->getBarionToken()
            && Mage::getModel('tlbarion/paymentmethod')->isTokenPaymentEnabled()) {
            $preparedBarionToken = $customer->getPreparedBarionToken();
            $customer->setBarionToken($preparedBarionToken)->save();
        }
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function isBarion($orderId)
    {
        return (bool)Mage::getModel('tlbarion/paymentmethod')
            ->getTransModel()
            ->loadByOrderId($orderId)
            ->getData('real_orderid');
    }

    /**
     * @param array $apiResultStatus
     * @param string $transactionStatus
     * @param string $transactionStatusId
     */
    public function getBarionTransactionStatus($apiResultStatus, $transactionStatus, $transactionStatusId)
    {
        $failedStatuses = ['Expired', 'Canceled', 'Rejected'];
        $pendingStatuses = ['Prepared', 'Started'];

        if ($apiResultStatus == 'Succeeded') {
            $transactionStatus = 'success';
            $transactionStatusId = self::SUCCESS_TRANSACTION_STATUS;
        }

        elseif ($apiResultStatus == 'Reserved') {
            $transactionStatus = 'reserved';
            $transactionStatusId = self::PENDING_TRANSACTION_STATUS;
        }

        elseif (in_array($apiResultStatus, $pendingStatuses)) {
            $transactionStatus = 'pending';
            $transactionStatusId = self::PENDING_TRANSACTION_STATUS;
        }

        elseif (in_array($apiResultStatus, $failedStatuses)) {
            $transactionStatus = 'fail';
            $transactionStatusId = self::FAILED_TRANSACTION_STATUS;
        }

        return [
            'transactionStatus'   => $transactionStatus,
            'transactionStatusId' => $transactionStatusId
        ];
    }

    /**
     * @param Nostress_Gpwebpay_Model_Order $order
     * @param bool $isFinishedByInvoice
     * @param array response
     * @param TLSoft_BarionPayment_Model_Transactions $transaction
     *
     * @return bool
     */
    public function processFinishReservationResponse($order, $isFinishedByInvoice, $response, $transaction)
    {
        if (empty($response['Errors'])) {
            $this->proccessSuccessFinishReservationResponse($order, $isFinishedByInvoice, $response, $transaction);
            return true;
        }

        $this->proccessFailedFinishReservationResponse($response);
        return false;
    }

    /**
     * @param Nostress_Gpwebpay_Model_Order $order
     * @param bool $isFinishedByInvoice
     * @param array $response
     * @param TLSoft_BarionPayment_Model_Transactions $transaction
     */
    public function proccessSuccessFinishReservationResponse($order, $isFinishedByInvoice, $response, $transaction)
    {
        if (!$isFinishedByInvoice) {
            $this->processOrderSuccess($order);
        }

        $transaction
            ->setData('bariontransactionid', $this->getSucceededTransaction($response['Transactions']))
            ->setData('payment_status', self::SUCCESS_TRANSACTION_STATUS)
            ->setData('real_orderid', $response['PaymentId'])
            ->save();

        Mage::getSingleton('adminhtml/session')->addSuccess('You have successfully finished a reservation.');
    }

    /**
     * @param array $response
     */
    public function proccessFailedFinishReservationResponse($response)
    {
        Mage::log($response, null, 'barion_reservation_errors.log', true);
        Mage::getSingleton('adminhtml/session')
            ->addError('Something went wrong. Reservation has not been finished.');
    }

    /**
     * @param array $response
     * @param TLSoft_BarionPayment_Model_Transactions $transaction
     *
     * @return bool
     */
    public function proccessRefundResponse($response, $transaction)
    {
        if (empty($response['Errors'])) {
            $this->proccessSuccessRefundResponse($transaction);
            return true;
        }

        $this->proccessFailedRefundResponse($response);
        return false;
    }

    /**
     * @param TLSoft_BarionPayment_Model_Transactions $transaction
     */
    public function proccessSuccessRefundResponse($transaction)
    {
        $transaction
            ->setData('payment_status', self::SUCCESS_TRANSACTION_STATUS)
            ->save();
        Mage::getSingleton('adminhtml/session')->addSuccess('Payment has been successfully refunded.');
    }

    /**
     * @param array $response
     */
    public function proccessFailedRefundResponse($response)
    {
        Mage::log($response, null, 'barion_refund_errors.log', true);
        Mage::getSingleton('adminhtml/session')
            ->addError('Something went wrong. Payment has not been refunded.');
    }
}
