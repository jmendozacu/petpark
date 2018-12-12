<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Mage_Adminhtml_Sales_Order_CreditmemoController
 */
require 'Mage/Adminhtml/controllers/Sales/Order/CreditmemoController.php';

class Virtua_BarionPayment_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Sales_Order_CreditmemoController
{
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('creditmemo');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            return $this->makeCreditmemo($data);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save the credit memo.'));
        }
        $this->_redirect('*/*/new', array('_current' => true));
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param array $postRequest
     */
    public function createCreditmemoComment($creditmemo, $postRequest): string
    {
        $comment = '';
        if (!empty($postRequest['comment_text'])) {
            $creditmemo->addComment(
                $postRequest['comment_text'],
                isset($postRequest['comment_customer_notify']),
                isset($postRequest['is_visible_on_front'])
            );
            if (isset($postRequest['comment_customer_notify'])) {
                $comment = $postRequest['comment_text'];
            }
        }
        return $comment;
    }

    /**
     * @param int $orderId
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @throws Exception
     */
    public function handleBarionPayment($orderId, $creditmemo)
    {
        if (Mage::helper('tlbarion')->isBarion($orderId)) {
            $total = $creditmemo->getData('base_grand_total');
            $total = bcdiv($total, 1, 2);
            if (!Mage::helper('tlbarion')->refundPayment($orderId, $total)) {
                throw new Exception();
            }
        }
    }

    /**
     * @param array $postRequest
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     */
    public function manageRefund($postRequest, $creditmemo)
    {
        if (isset($postRequest['do_refund'])) {
            $creditmemo->setRefundRequested(true);
        }
        if (isset($postRequest['do_offline'])) {
            $creditmemo->setOfflineRequested((bool)(int)$postRequest['do_offline']);
        }
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @throws Mage_Core_Exception
     */
    public function checkIsCreditmemoPositive($creditmemo)
    {
        if (($creditmemo->getGrandTotal() <= 0) && (!$creditmemo->getAllowZeroGrandTotal())) {
            Mage::throwException(
                $this->__('Credit memo\'s total must be positive.')
            );
        }
    }

    /**
     * @param array $postRequest
     */
    public function makeCreditmemo($postRequest)
    {
        $creditmemo = $this->_initCreditmemo();
        if ($creditmemo) {
            $this->checkIsCreditmemoPositive($creditmemo);
            $comment = $this->createCreditmemoComment($creditmemo, $postRequest);

            $orderId = $creditmemo->getData('order_id');
            $this->handleBarionPayment($orderId, $creditmemo);
            $this->manageRefund($postRequest, $creditmemo);

            $creditmemo->register();
            if (!empty($postRequest['send_email'])) {
                $creditmemo->setEmailSent(true);
            }

            $creditmemo->getOrder()->setCustomerNoteNotify(!empty($postRequest['send_email']));
            $this->_saveCreditmemo($creditmemo);
            $creditmemo->sendEmail(!empty($postRequest['send_email']), $comment);
            $this->_getSession()->addSuccess($this->__('The credit memo has been created.'));
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
            $this->_redirect('*/sales_order/view', array('order_id' => $creditmemo->getOrderId()));
            return;
        }
        $this->_forward('noRoute');
        return;
    }
}
