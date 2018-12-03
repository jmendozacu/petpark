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

        $transid      = $transaction->getEntityId();
        $real_orderid = $transaction->getRealOrderid();
        $params       = '?POSKey='.$this->getShopId($storeid).'&TransactionId='.$transaction->getBariontransactionid();
        $result       = $this->callCurl2($params, $storeid);
        $resultarray  = [];
        $return       = 'pending';

        if ($result != false) {
            $resultarray = json_decode($result, true);
            if ($resultarray['Status'] == 'Succeeded') {
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
            $resultarray = json_decode($result, true);
            if (empty($resultarray['Errors'])) {
                $transaction
                    ->setData('payment_status', '02')
                    ->save();
                Mage::getSingleton('adminhtml/session')->addSuccess('Payment has been successfully refunded.');
                return true;
            }
            Mage::log($resultarray, null, 'barion_refund_errors.log', true);
            Mage::getSingleton('adminhtml/session')
                ->addError('Something went wrong. Payment has not been refunded.');
            return false;
        }
    }

    /**
     * Finishing reserved payment.
     *
     * @param int $orderId
     *
     * @param float|null $total
     * @param bool|null $isFinishedByInvoice
     * @param array|null $items
     *
     * @return bool
     */
    public function finishReservation($orderId, $total = null, $isFinishedByInvoice = null, $items = null)
    {
        $order         = Mage::getModel('sales/order')->load($orderId);
        $storeid       = $order->getStoreId();
        $transaction   = Mage::getModel('tlbarion/paymentmethod')->getTransModel()->loadByOrderId($orderId);
        $transactionId = $transaction->getBariontransactionid();
        $paymentId     = $transaction->getData('real_orderid');

        if (!$total) {
            $total = $order->getGrandTotal();
        }

        $header = [
            'POSKey'    => $this->getShopId($storeid),
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
        $result = $this->callBarionToFinishReservation($json, $storeid);

        if ($result != false) {
            $resultarray = json_decode($result, true);
            if (empty($resultarray['Errors'])) {
                if (!$isFinishedByInvoice) {
                    $this->processOrderSuccess($order);
                }
                $transaction
                    ->setData('bariontransactionid', $this->getSucceededTransaction($resultarray['Transactions']))
                    ->setData('payment_status', '02')
                    ->setData('real_orderid', $resultarray['PaymentId'])
                    ->save();
                Mage::getSingleton('adminhtml/session')->addSuccess('You have successfully finished a reservation.');
                return true;
            }
            Mage::log($resultarray, null, 'barion_reservation_errors.log', true);
            Mage::getSingleton('adminhtml/session')
                ->addError('Something went wrong. Reservation has not been finished.');
            return false;
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
}