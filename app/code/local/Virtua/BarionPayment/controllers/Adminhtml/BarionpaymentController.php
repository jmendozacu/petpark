<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Adminhtml_BarionpaymentController
 */
class Virtua_BarionPayment_Adminhtml_BarionpaymentController extends Mage_Adminhtml_Controller_Action
{
    public function refundAction()
    {
        Mage::helper('tlbarion')->refundPayment($this->getOrderIdParameter());
        $this->redirectToOrderView();
    }

    public function finishReservationAction()
    {
        Mage::helper('tlbarion')->finishReservation($this->getOrderIdParameter());
        $this->redirectToOrderView();
    }

    /**
     * @return int
     */
    public function getOrderIdParameter()
    {
        return $this->getRequest()->getParam('orderId');
    }

    public function redirectToOrderView()
    {
        $this->_redirectUrl(Mage::helper('adminhtml')->getUrl(
            '*/sales_order/view',
            ['order_id' => $this->getOrderIdParameter()]
        ));
    }
}
