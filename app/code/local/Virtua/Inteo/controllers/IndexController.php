<?php

class Virtua_Inteo_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $helper = Mage::helper('virtua_inteo');
        $helper->createApiConnection();
        //$this->getResponse()->setHeader('Content-type', 'application/json');
        //$this->getResponse()->setBody();
    }
}