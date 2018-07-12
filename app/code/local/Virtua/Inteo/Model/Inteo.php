<?php

class Virtua_Inteo_Model_Inteo extends Mage_Core_Model_Abstract
{
    const TYPE_ID_ITEM = 1;
    const TYPE_ID_DELIVERY = 2;

    /**
     * Retrieves orders collection
     * @return mixed
     */
    public function getOrderCollection()
    {
        $lastTransferredOrderDate = Mage::helper('virtua_inteo')->getLastTransferredOrderDate();
        if (!$lastTransferredOrderDate) {
            $lastTransferredOrderDate = '2018-07-12T00:00:00+0000';
        }

        $collection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('updated_at', ['gteq' => $lastTransferredOrderDate])
            ->addFieldToFilter('status', ['neq' => 'canceled'])
            ->setOrder('entity_id', 'desc');

        return $collection;
    }

    /**
     * Retrieves full json data to transfer
     * @return string
     */
    public function getJsonData()
    {
        $out = [];
        $orderCollection = $this->getOrderCollection();
        foreach ($orderCollection as $key => $order) {
            $out[] = $this->prepareSingleOrder($order);
        }

        return json_encode($out);
    }

    /**
     * Parses to float number format
     * @param $price
     * @param int $decimals
     * @param bool $onlyPositive
     * @return float
     */
    private function getNumberFormat($price, $decimals = 2, $onlyPositive = true)
    {
        if ($onlyPositive) {
            $price = abs($price);
        }
        $number = number_format($price, $decimals);
        return ($number !== 'nan') ? $number : 0.00;
    }

    /**
     * Preparing data of single order
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    private function prepareSingleOrder(Mage_Sales_Model_Order $order)
    {
        $clientName = $order->getBillingAddress()->getCompany() ?
            $order->getBillingAddress()->getCompany() :
            $order->getCustomerName();

        $orderArray = [
            'documentNumber' => $order->getIncrementId(),
            'totalPriceWithVat' => $this->getNumberFormat($order->getGrandTotal()),
            'createDate' => date(DATE_ISO8601, strtotime($order->getCreatedAt())),
            'clientName' => $clientName,
            'clientContactName' => $order->getCustomerFirstname(),
            'clientContactSurname' => $order->getCustomerLastname(),
            'clientStreet' => $order->getBillingAddress()->getStreetFull(),
            'clientPostCode' => $this->stringLength($order->getBillingAddress()->getPostcode(), 20),
            'clientTown' => $order->getBillingAddress()->getCity(),
            'clientCountry' => $order->getBillingAddress()->getCountry(),
            'clientPhone' => $order->getBillingAddress()->getTelephone(),
            'clientEmail' => $order->getBillingAddress()->getEmail(),
            'clientRegistrationId' => $order->getData('customer_taxvat'),
            'clientTaxId' => $order->getCustomerId(),
            'clientVatId' => $order->getBillingAddress()->getData('vat_id'),
            'clientInternalId' => '',
            'openingText' => '',
            'closingText' => '',
            'paymentType' => $order->getPayment()->getMethod(),
            'deliveryType' => $order->getShippingMethod(),
            'clientPostalName' => $order->getShippingAddress()->getName(),
            'clientPostalContactName' => $order->getShippingAddress()->getFirstname(),
            'clientPostalContactSurname' => $order->getShippingAddress()->getLastname(),
            'clientPostalPhone' => $order->getShippingAddress()->getTelephone(),
            'clientPostalStreet' => $order->getShippingAddress()->getStreetFull(),
            'clientPostalPostCode' => $this->stringLength($order->getShippingAddress()->getPostcode(), 20),
            'clientPostalTown' => $order->getShippingAddress()->getCity(),
            'clientPostalCountry' => $order->getShippingAddress()->getCountry(),
            'clientHasDifferentPostalAddress' => false,
            'currency' => $order->getBaseCurrencyCode(),
            'exchangeRate' => '',
            'discountPercent' => null,
            'discountValueWithVat' => $this->getNumberFormat($order->getDiscountAmount()),
            'priceDecimalPlaces' => '',
            'clientNote' => $order->getCustomerNote(),
            'items' => [],
        ];

        if ($order->getAllVisibleItems()) {
            foreach ($order->getAllVisibleItems() as $item) {
                $orderArray['items'][] = $this->prepareSingleItem($item);
            }
        }

        return $orderArray;
    }

    /**
     * Preparing data of single item
     * @param Mage_Sales_Model_Order_Item $item
     * @return array
     */
    private function prepareSingleItem(Mage_Sales_Model_Order_Item $item)
    {
        $product = $this->getProductBySku($item->getSku());
        $itemArray = [
            'name' => $product->getData('name'),
            'count' => (int) $item->getQtyOrdered(),
            'measureType' => '',
            'totalPriceWithVat' => $this->getNumberFormat($item->getRowTotalInclTax()),
            'unitPriceWithVat' => $this->getNumberFormat($item->getPriceInclTax()),
            'vat' => $this->getNumberFormat($item->getTaxPercent()),
            'productCode' => $item->getSku(),
            'typeId' => self::TYPE_ID_ITEM,
            'warehouseCode' => 'SC',
            'foreignName' => '',
            'ean' => $this->stringLength($product->getData('ean'),18),
            'jkpov' => '',
            'plu' => 0,
            'numberingSequenceCode' => '',
            'specialAttribute' => null,
        ];
        return $itemArray;
    }

    /**
     * Get product model by its sku
     * @param $sku
     * @return mixed
     */
    public function getProductBySku($sku)
    {
        return Mage::getModel('catalog/product')
            ->loadByAttribute('sku', $sku);
    }

    /**
     * @param $string
     * @param $maxLength
     * @return bool|string
     */
    private function stringLength($string, $maxLength)
    {
        if (strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength);
        }
        return $string;
    }
}