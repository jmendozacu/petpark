<?php

class Virtua_Inteo_Model_Inteo extends Mage_Core_Model_Abstract
{
    const TYPE_ID_ITEM = 1;
    const TYPE_ID_DELIVERY = 2;

    public function getOrderCollection()
    {
        $lastWeek = date(DATE_ISO8601, strtotime("-1 week +1 day"));
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('created_at', ['gteq' => $lastWeek])
            ->addFieldToFilter('status', ['neq' => 'canceled'])
            ->setOrder('entity_id', 'desc');

        return $collection;
            //->addFieldToFilter('created_at', ['gteq' => $previousWeek]);
    }

    public function getJsonData()
    {
        $out = [];
        $orderCollection = $this->getOrderCollection();
        foreach ($orderCollection as $key => $order) {
            //\Zend_Debug::dump($order->getData());
            $out[] = $this->prepareSingleOrder($order);
        }
        //\Zend_Debug::dump($out);
        //exit;
        return json_encode($out);
    }

    private function getDiscountPercentByOrder(Mage_Sales_Model_Order $order)
    {
        $discount = ($order->getDiscountAmount()) ?
            $order->getDiscountAmount() / $order->getGrandTotal() *  100 :
            0;
        return $this->getNumberFormat($discount);
    }

    private function getNumberFormat($price, $decimals = 2, $onlyPositive = true)
    {
        if ($onlyPositive) {
            $price = abs($price);
        }
        $number = number_format($price, $decimals);
        return ($number !== 'nan') ? $number : 0.00;
    }

    private function prepareSingleOrder(Mage_Sales_Model_Order $order)
    {
        $discountPercent = $this->getDiscountPercentByOrder($order);
        $orderArray = [
            'documentNumber' => $order->getIncrementId(),
            'totalPriceWithVat' => $this->getNumberFormat($order->getGrandTotal()),
            'createDate' => date(DATE_ISO8601, strtotime($order->getCreatedAt())),
            'clientName' => $order->getCustomerName(),
            'clientContactName' => $order->getCustomerFirstname(),
            'clientContactSurname' => $order->getCustomerLastname(),
            'clientStreet' => $order->getBillingAddress()->getStreetFull(),
            'clientPostCode' => $this->stringLength($order->getBillingAddress()->getPostcode(), 20),
            'clientTown' => $order->getBillingAddress()->getCity(),
            'clientCountry' => $order->getBillingAddress()->getCountry(),
            'clientPhone' => $order->getBillingAddress()->getTelephone(),
            'clientEmail' => $order->getBillingAddress()->getEmail(),
            'clientRegistrationId' => $order->getBillingAddress()->getData('vat_id'),
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
            'discountPercent' => $discountPercent,
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

    private function prepareSingleItem(Mage_Sales_Model_Order_Item $item)
    {
        //\Zend_Debug::dump($item->getData());
        $product = Mage::getModel('catalog/product')->load($item->getProductId());
        $itemArray = [
            'name' => $item->getName(),
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

    private function stringLength($string, $maxLength)
    {
        if (strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength);
        }
        return $string;
    }
}