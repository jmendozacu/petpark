<?php
/**
 * @category  TlSoft
 * @package   Virtua_TlSoft
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_TLSoft_Helper_Data
 */
class Virtua_TLSoft_Helper_Data extends TLSoft_BarionPayment_Helper_Data
{
    protected $_testrefund      = 'https://api.test.barion.com/v2/Payment/Refund';
    protected $_refund          = 'https://api.barion.com/v2/Payment/Refund';
    protected $_testreservation = 'https://api.test.barion.com/v2/Payment/FinishReservation';
    protected $_reservation     = 'https://api.barion.com/v2/Payment/FinishReservation';

    /**
     * @param string $order
     * @param array $transaction
     * @return string
     */
    public function processTransResult($order = '', $transaction = array())
    {
        $helper = $this;
        $otppayment = Mage::getModel('tlbarion/paymentmethod');

        if (empty($order)) {
            $order = $this->getCurrentOrder();
        }

        $storeid = $order->getStoreId();

        $storeId = $order->getStoreId();
        if (is_array($transaction)) {
            $transaction = $otppayment->getTransModel()->loadByOrderId($order->getId());
        }

        $transid = $transaction->getEntityId();

        $real_orderid = $transaction->getRealOrderid();

        $params = '?POSKey='.$helper->getShopId($storeid).'&TransactionId='.$transaction->getBariontransactionid();

        $result = $this->callCurl2($params, $storeId);

        $resultarray = array();

        $return = 'pending';
        if ($result != false) {
            $resultarray = json_decode($result, true);
            if ($resultarray['Status']=='Succeeded') {
                $return = 'success';
                $status = '02';
            } elseif ($resultarray['Status'] == 'Prepared'
                || $resultarray['Status'] == 'Started'
                || $resultarray['Status'] == 'Reserved') {
                $return = 'pending';
                $status = '01';
            } elseif ($resultarray['Status'] == 'Failed'
                ||$resultarray['Status'] == 'Expired'
                ||$resultarray['Status'] == 'Canceled'
                ||$resultarray['Status'] == 'Rejected') {
                $return = 'fail';
                $status = '00';
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
     * @param $orderId
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
            $this->refundInAdmin($order);
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
            $transaction
                ->setData('payment_status', '02')
                ->save();
            Mage::getSingleton('adminhtml/session')->addSuccess('You have successfully refunded.');
        } else {
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong. Payment has not refunded.');
        }
    }

    /**
     * @param $orderId
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
            $this->processOrderSuccess($order);
            $transaction
                ->setData('bariontransactionid', $this->getSucceededTransaction($resultarray['Transactions']))
                ->setData('payment_status', '02')
                ->setData('real_orderid', $resultarray['PaymentId'])
                ->save();
            Mage::getSingleton('adminhtml/session')->addSuccess('You have successfully finished a reservation.');
        } else {
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong. Reservation has not finished.');
        }
    }

    /**
     * @param $json
     * @param $storeId
     * @return bool|mixed
     */
    public function callBarionToRefund($json, $storeId)
    {
        if ($this->isTest($storeId) == 1) {
            $url = $this->_testrefund;
        } else {
            $url = $this->_refund;
        }

        try {
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_URL            => $url,
                CURLOPT_POST           => 1,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Content-Length: ' . strlen($json)]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            $err     = curl_errno($ch);

            if (!$err) {
                curl_close($ch);
                return $content;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $json
     * @param $storeId
     * @return bool|mixed
     */
    public function callBarionToFinishReservation($json, $storeId)
    {
        if ($this->isTest($storeId) == 1) {
            $url = $this->_testreservation;
        } else {
            $url = $this->_reservation;
        }

        try {
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_URL            => $url,
                CURLOPT_POST           => 1,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Content-Length: ' . strlen($json)]
            ];
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            $err = curl_errno($ch);
            if (!$err) {
                curl_close($ch);
                return $content;
            }
            Mage::log('Barion curl error: '.$err);
            return false;
        } catch (Exception $e) {
            Mage::log($e);
            return false;
        }
    }

    /**
     * @param $order
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

        if ($refundToStoreCreditAmount) {
            $refundToStoreCreditAmount = max(
                0,
                min(
                    $creditmemo->getBaseCustomerBalanceReturnMax(),
                    $refundToStoreCreditAmount
                )
            );
            if ($refundToStoreCreditAmount) {
                $refundToStoreCreditAmount = $creditmemo->getStore()->roundPrice($refundToStoreCreditAmount);
                $creditmemo->setBaseCustomerBalanceTotalRefunded($refundToStoreCreditAmount);
                $refundToStoreCreditAmount = $creditmemo->getStore()->roundPrice(
                    $refundToStoreCreditAmount * $order->getStoreToOrderRate()
                );
                $creditmemo->setBsCustomerBalTotalRefunded($refundToStoreCreditAmount);
                $creditmemo->setCustomerBalanceRefundFlag(true);
            }
        }
        $creditmemo->setPaymentRefundDisallowed(true)->register();
        try {
            Mage::getModel('core/resource_transaction')
                ->addObject($creditmemo)
                ->addObject($order)
                ->save();
        } catch (Mage_Core_Exception $e) {
            return false;
        }
    }

    public function processOrderSuccess($order)
    {
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
    }

    /**
     * @param $transactions
     * @return string
     */
    public function getSucceededTransaction($transactions)
    {
        $transaction = end($transactions);
        return $transaction['TransactionId'];
    }
}
