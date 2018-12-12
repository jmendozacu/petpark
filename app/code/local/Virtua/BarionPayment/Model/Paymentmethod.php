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
    const REDIRECT_URL = 'tlbarion/redirection/respond';
    const TOKEN_PREFIX = 'PETPARK-XMLP-TOKEN-';

    protected $_canAuthorize            = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture              = false;
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = true;

    /**
     * @return bool|string
     */
    public function getOtpUrl()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $helper   = $this->otpHelper();
        $order    = $helper->getCurrentOrder();
        if (!$order->getId()) {
            return false;
        }
        $storeId = $order->getStoreId();
        $header  = $this->prepareRequest($order);

        if ($this->isTokenPaymentEnabled()) {
            $header = $this->initiateTokenRequest($customer, $header);
        } elseif ($customer->getBarionToken()) {
            $this->resetToken($customer);
        }

        if ($this->isReservedPaymentEnabled()) {
            $header = $this->initiateReservedPayment($header);
        }

        $connect = $this->sendRequest($header, $storeId);
        $apiResult = json_decode($connect, true);

        if ($this->wasTokenRequestFailed($header)) {
            $header  = $this->dontUseExsistingToken($header);
            $connect = $this->sendRequest($header, $storeId);
            $apiResult  = json_decode($connect, true);
        }

        if ($connect != false) {
            Mage::log($apiResult, null, 'barion_payment_results.log', true);
            $this->setTokenIfItWasntUsed($customer, $header);
            return $this->proccessResponse($apiResult, $order, $header);
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

        return self::TOKEN_PREFIX.$uniqueCode.'-'.$customerId;
    }

    /**
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isTokenPaymentEnabled()
    {
        $isTokenEnabled = Mage::getStoreConfig('payment/tlbarion/virtua_barionpayment_token', Mage::app()->getStore());
        return $isTokenEnabled && Mage::getSingleton('customer/session')->isLoggedIn();
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
        $header['RedirectUrl'] = Mage::getUrl(self::REDIRECT_URL);
        unset($header['CallbackUrl']);

        return $header;
    }

    /**
     * @return bool
     */
    public function wasOriginalPaymentUnsuccessful($resultarray)
    {
        if (!array_key_exists('Errors', $resultarray)) {
            return false;
        }

        foreach ($resultarray['Errors'] as $error) {
            if (array_key_exists('ErrorCode', $error)
                && $error['ErrorCode'] === 'OriginalPaymentWasntSuccessful')
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Nostress_Gpwebpay_Model_Order $order
     *
     * @return array
     */
    public function prepareRequest($order)
    {
        $lastOrderId = $order->getIncrementId();
        $request = [
            'POSKey'           => $this->otpHelper()->getShopId($order->getStoreId()),
            'PaymentType'      => 'Immediate',
            'PaymentWindow'    => '00:30:00',
            'GuestCheckOut'    => true,
            'FundingSources'   => ['All'],
            'PaymentRequestId' => $lastOrderId,
            'RedirectUrl'      => Mage::getUrl(self::REDIRECT_URL),
            'currency'         => $order->getOrderCurrencyCode(),
            'locale'           => 'sk-SK',
            'Transactions'     => [[
                'POSTransactionId' => $lastOrderId,
                'Payee'            => $this->otpHelper()->getEmail($order->getStoreId()),
                'Total'            => $order->getGrandTotal(),
                'Items'            => $this->getCurrentOrderProducts($order)
            ]]
        ];

        return $request;
    }

    /**
     * @param Nostress_Gpwebpay_Model_Order $order
     *
     * @return array
     */
    public function getCurrentOrderProducts($order)
    {
        $items = $order->getAllVisibleItems();
        $counter = 0;

        foreach ($items as $item) {
            $products[$counter]['Name']        = $item->getName();
            $products[$counter]['Description'] = $item->getName();
            $products[$counter]['Quantity']    = $item->getQtyOrdered();
            $products[$counter]['Unit']        = 'db';
            $products[$counter]['UnitPrice']   = $item->getPriceInclTax();
            $products[$counter]['ItemTotal']   = $item->getRowTotalInclTax();
            $counter++;
        }

        $shipping = $order->getShippingInclTax();

        if ($shipping > 0) {
            $products[$counter]['Name']        = $order->getShippingDescription();
            $products[$counter]['Description'] = $order->getShippingDescription();
            $products[$counter]['Quantity']    = 1;
            $products[$counter]['Unit']        = 'db';
            $products[$counter]['UnitPrice']   = $shipping;
            $products[$counter]['ItemTotal']   = $shipping;
        }

        return $products;
    }

    /**
     * @param Dotdigitalgroup_Email_Model_Customer $customer
     * @param array $request
     *
     * @return array
     */
    public function initiateTokenRequest($customer, $request)
    {
        if ($customer->getBarionToken() != null) {
            $request['RecurrenceId'] = $customer->getBarionToken();
            $request['InitiateRecurrence'] = false;
            unset($request['RedirectUrl']);
            $request['CallbackUrl'] = Mage::getUrl(self::REDIRECT_URL);
        } else {
            $request['RecurrenceId'] = $this->prepareToken();
            $request['InitiateRecurrence'] = true;
        }

        return $request;
    }

    /**
     * @param Dotdigitalgroup_Email_Model_Customer $customer
     */
    public function resetToken($customer)
    {
        $customer
            ->setBarionToken(null)
            ->setPreparedBarionToken(null)
            ->save();
    }

    public function isReservedPaymentEnabled(): bool
    {
        return 'reservation' === Mage::getStoreConfig('payment/tlbarion/virtua_barionpayment_paymenttype', Mage::app()->getStore());
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function initiateReservedPayment($request)
    {
        $request['PaymentType'] = 'Reservation';
        $reservationPeriod = Mage::getStoreConfig(
            'payment/tlbarion/virtua_barionpayment_reservation_period',
            Mage::app()->getStore()
        );

        if ($reservationPeriod) {
            $request['ReservationPeriod'] = $reservationPeriod;
        } else {
            $request['ReservationPeriod'] = '1.00:00:00';
        }

        return $request;
    }

    /**
     * @param array $request
     * @param int $storeId
     *
     * @return mixed
     */
    public function sendRequest($request, $storeId)
    {
        $json = json_encode($request);
        return $this->otpHelper()->callCurl($json, $storeId);
    }

    /**
     * @param array $request
     */
    public function wasTokenRequestFailed($request): bool
    {
        return $request['InitiateRecurrence'] == false
            && ($this->areFundsInsufficient($resultarray)
                || $this->wasOriginalPaymentUnsuccessful($resultarray));
    }

    /**
     * @param Nostress_Gpwebpay_Model_Order $order
     * @param array $apiResult
     */
    public function saveTransaction($order, $apiResult)
    {
        $transId = $this->saveTrans([
            'real_orderid'   => $apiResult['PaymentId'],
            'order_id'       => $order->getId(),
            'application_id' => $this->otpHelper()->getShopId($order->getStoreId()),
            'amount'         => $order->getGrandTotal(),
            'ccy'            => $order->getOrderCurrencyCode(),
            'store_id'       => $order->getStoreId(),
            'payment_status' => $this->otpHelper()::PENDING_TRANSACTION_STATUS,
            'created_at'     => now()
        ]);

        $this->saveTransHistory([
            'transaction_id' => $transId,
            'created_at'     => now()
        ]);

        $this->updateTrans(
            [
                'bariontransactionid' => $apiResult['Transactions'][0]['TransactionId']
            ],
            $transId
        );
    }

    /**
     * @param array $apiResult
     * @param Nostress_Gpwebpay_Model_Order $order
     * @param array $request
     *
     * @return bool|string
     */
    public function proccessResponse($apiResult, $order, $request)
    {
        if (array_key_exists('PaymentId', $apiResult)) {
            $this->saveTransaction($order, $apiResult);

            if (array_key_exists('InitiateRecurrence', $request) && $request['InitiateRecurrence'] == false) {
                $url = Mage::getUrl(self::REDIRECT_URL);
                return $url;
            }
            $url = $this->otpHelper()->getRedirectUrl($order->getStoreId());
            return $url . '?id=' . $apiResult['PaymentId'];
        }

        return $this->handleErrors($apiResult['Errors']);
    }

    /**
     * @param array $errors
     */
    public function handleErrors($errors): bool
    {
        foreach ($errors as $error) {
            $this->saveTransHistory([
                'created_at'     => now(),
                'error_message'  => $error['Description'],
                'error_number'   => $error['ErrorCode']]);
        }

        return false;
    }

    /**
     * @param Dotdigitalgroup_Email_Model_Customer $customer
     * @param array $request
     */
    public function setTokenIfItWasntUsed($customer, $request)
    {
        if ($customer->getBarionToken() == null && $this->isTokenPaymentEnabled()) {
            $customer->setPreparedBarionToken($request['RecurrenceId'])->save();
        }
    }
}
