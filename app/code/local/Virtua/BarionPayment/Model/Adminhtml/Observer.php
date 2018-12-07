<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Model_Adminhtml_Observer
 */
class Virtua_BarionPayment_Model_Adminhtml_Observer
{
    /**
     * @param $observer
     */
    public function manageBarionOrder($observer)
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');

        $isBarion = Mage::getModel('tlbarion/paymentmethod')
            ->getTransModel()
            ->loadByOrderId($orderId)
            ->getData('real_orderid');

        if ($isBarion) {
            $this->loadButtons($orderId);
            $this->completeOrder($orderId);
        }
    }

    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function loadButtons($orderId)
    {
        $block = Mage::app()->getLayout()->getBlock('sales_order_edit');

        if (!$block) {
            return $this;
        }

        $order = Mage::getModel('sales/order')->load($orderId);
        $status = $order->getStatus();
        $qtyInvoiced = $this->getQtyInvoiced($order->getAllVisibleItems());

        if ($qtyInvoiced == 0 && $status !== Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $this->createFinishReservationButton($block, $orderId);
        }

        if ($qtyInvoiced > 0 || $status === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $block->removeButton('order_invoice');
        }

        return $this;
    }

    /**
     * @param $block
     * @param int $orderId
     */
    public function createFinishReservationButton($block, $orderId)
    {
        $url = Mage::helper('adminhtml')->getUrl(
            '*/barionpayment/finishReservation',
            ['orderId' => $orderId]
        );

        $block->addButton('barion_finish_reservation', array(
            'label'     => Mage::helper('sales')->__('Finish reservation'),
            'onclick'   => 'setLocation(\'' . $url . '\')',
            'class'     => 'go'
        ));
    }

    /**
     * @param array $items
     *
     * @return int
     */
    public function getQtyInvoiced($items)
    {
        $qty = 0;

        foreach ($items as $item) {
            $qty += $item->getQtyInvoiced();
        }

        return $qty;
    }

    /**
     * @param array $items
     *
     * @return int
     */
    public function getQtyShipped($items)
    {
        $qty = 0;

        foreach ($items as $item) {
            $qty += $item->getQtyShipped();
        }

        return $qty;
    }

    /**
     * @param int $orderId
     */
    public function completeOrder($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);

        if ($order->getState() === Mage_Sales_Model_Order::STATE_PROCESSING
            && $this->getQtyInvoiced($order->getAllVisibleItems())
            && $this->getQtyShipped($order->getAllVisibleItems()))
        {
            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
            $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
            $order->save();
            $this->reloadPage();
        }
    }

    public function reloadPage()
    {
        Mage::app()
            ->getFrontController()
            ->getResponse()
            ->setRedirect(Mage::helper('core/url')->getCurrentUrl());
    }
}
