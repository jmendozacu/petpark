<?php

class Virtua_Inteo_Adminhtml_OmegaController extends Mage_Adminhtml_Controller_Action
{
    public function transferAction()
    {
        $helper = Mage::helper('virtua_inteo');
        if ($helper->transferData()) {
            Mage::getSingleton('core/session')->addSuccess('Success. Orders have been transferred into the Omega.');
            $helper->setLastTransferredOrderDate();
        } else {
            Mage::getSingleton('core/session')->addError('Error. Orders have not been transferred into the Omega.');
        }
        $this->_redirect('*/sales_order/index');
    }
}