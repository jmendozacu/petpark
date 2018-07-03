<?php

class Virtua_Inteo_Model_Observer
{
    public function addNewButton($observer)
    {
        $container = $observer->getBlock();
        if (null !== $container && $container->getType() == 'adminhtml/sales_order') {
            $data = array(
                'label'     => 'Send orders to Omega',
                'class'     => '',
                'onclick'   => 'setLocation(\''  . Mage::helper('adminhtml')->getUrl('*/omega/transfer') . '\')',
            );
            $container->addButton('omega-order-sync', $data);
        }

        return $this;
    }
}