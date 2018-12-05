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
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function loadButtons($observer)
    {
        //$order->getAllVisibleItems();
        $block = Mage::app()->getLayout()->getBlock('sales_order_edit');

        if (!$block) {
            return $this;
        }
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $status = $order->getStatus();

        $isBarion = Mage::getModel('tlbarion/paymentmethod')
            ->getTransModel()
            ->loadByOrderId($orderId)
            ->getData('real_orderid');

        $qtyInvoiced = $this->getQtyInvoiced($order->getAllVisibleItems());

        if ($qtyInvoiced == 0 && $isBarion && $status !== 'pending_payment') {
            $this->createFinishReservationButton($block, $orderId);
        }

        if (($qtyInvoiced > 0 || $status === 'pending_payment') && $isBarion) {
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
}
