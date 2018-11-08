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
        $block = Mage::app()->getLayout()->getBlock('sales_order_edit');
        if (!$block) {
            return $this;
        }
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $state = Mage::getModel('sales/order')->load($orderId)->getState();
        $isBarion = Mage::getModel('tlbarion/paymentmethod')
            ->getTransModel()
            ->loadByOrderId($orderId)
            ->getData('real_orderid');

        if (($state == 'processing' || $state == 'complete') && $isBarion) {
            $this->createRefundButton($block, $orderId);
        } elseif ($state == 'payment_review' && $isBarion) {
            $this->createFinishReservationButton($block, $orderId);
        }

        return $this;
    }

    /**
     * @param $block
     * @param $orderId
     */
    public function createRefundButton($block, $orderId)
    {
        $url = Mage::helper('adminhtml')->getUrl(
            '*/barionpayment/refund',
            ['orderId' => $orderId]
        );

        $block->addButton('barion_refund', array(
            'label'     => Mage::helper('sales')->__('Refund Barion Payment'),
            'onclick'   => 'setLocation(\'' . $url . '\')',
            'class'     => 'go'
        ));
    }

    /**
     * @param $block
     * @param $orderId
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
}
