<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Mage_Adminhtml_Sales_Order_InvoiceController
 */
require 'Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php';

class Virtua_BarionPayment_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController
{
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('invoice');
        $orderId = $this->getRequest()->getParam('order_id');

        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $this->makeInvoice($data, $orderId);
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Unable to save the invoice.'));
            Mage::logException($e);
        }
        $this->_redirect('*/*/new', array('order_id' => $orderId));
    }

    /**
     * @param array $postRequest
     * @param Mage_Sales_Model_Order_Invoice $invoice
     */
    public function manageCaptureCase($postRequest, $invoice)
    {
        if (!empty($postRequest['capture_case'])) {
            $invoice->setRequestedCaptureCase($postRequest['capture_case']);
        }
    }

    /**
     * @param array $postRequest
     * @param Mage_Sales_Model_Order_Invoice $invoice
     */
    public function createInvoiceComment($postRequest, $invoice)
    {
        if (!empty($postRequest['comment_text'])) {
            $invoice->addComment(
                $postRequest['comment_text'],
                isset($postRequest['comment_customer_notify']),
                isset($postRequest['is_visible_on_front'])
            );
        }
    }

    /**
     * @param int $orderId
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @throws Exception
     */
    public function handleBarionPayment($orderId, $invoice)
    {
        if (Mage::helper('tlbarion')->isBarion($orderId)) {
            $items = $invoice->getAllItems();
            $total = $invoice->getData('base_grand_total');
            $total = bcdiv($total, 1, 2);
            if (!Mage::helper('tlbarion')->finishReservation($orderId, $total, true, $items)) {
                throw new Exception();
            }
        }
    }

    /**
     * @param array $postRequest
     */
    public function giveInvoiceInfoAccordingToShipment($postRequest)
    {
        if (!empty($postRequest['do_shipment'])) {
            $this->_getSession()->addSuccess($this->__('The invoice and shipment have been created.'));
        } else {
            $this->_getSession()->addSuccess($this->__('The invoice has been created.'));
        }
    }

    /**
     * @param array $postRequest
     * @param int $orderId
     */
    public function makeInvoice($postRequest, $orderId)
    {
        $invoice = $this->_initInvoice();
        if ($invoice) {
            $this->handleInvoice($postRequest, $invoice);
        } else {
            $this->_redirect('*/*/new', array('order_id' => $orderId));
        }
    }

    /**
     * @param array $postRequest
     * @param Mage_Sales_Model_Order_Invoice $invoice
     */
    public function tryToSendInvoiceEmail($postRequest, $invoice)
    {
        $comment = '';

        if (isset($postRequest['comment_customer_notify'])) {
            $comment = $postRequest['comment_text'];
        }

        try {
            $invoice->sendEmail(!empty($postRequest['send_email']), $comment);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Unable to send the invoice email.'));
        }
    }

    /**
     * @param array $postRequest
     * @param Mage_Sales_Model_Order_Shipment $shipment
     */
    public function tryToSendShipmentEmail($postRequest, $shipment)
    {
        if ($shipment) {
            try {
                $shipment->sendEmail(!empty($postRequest['send_email']));
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($this->__('Unable to send the shipment email.'));
            }
        }
    }

    /**
     * @param array $postRequest
     * @param Mage_Sales_Model_Order_Invoice $invoice
     */
    public function handleInvoice($postRequest, $invoice)
    {
        $this->manageCaptureCase($postRequest, $invoice);
        $this->createInvoiceComment($postRequest, $invoice);

        $orderId = $invoice->getData('order_id');
        $this->handleBarionPayment($orderId, $invoice);

        $invoice->register();

        if (!empty($postRequest['send_email'])) {
            $invoice->setEmailSent(true);
        }

        $invoice->getOrder()->setCustomerNoteNotify(!empty($postRequest['send_email']));
        $invoice->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $shipment = false;
        if (!empty($postRequest['do_shipment']) || (int) $invoice->getOrder()->getForcedDoShipmentWithInvoice()) {
            $shipment = $this->_prepareShipment($invoice);
            if ($shipment) {
                $shipment->setEmailSent($invoice->getEmailSent());
                $transactionSave->addObject($shipment);
            }
        }
        $transactionSave->save();
        $this->giveInvoiceInfoAccordingToShipment($postRequest);
        $this->tryToSendInvoiceEmail($postRequest, $invoice);
        $this->tryToSendShipmentEmail($postRequest, $shipment);
        Mage::getSingleton('adminhtml/session')->getCommentText(true);
        $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
    }
}
