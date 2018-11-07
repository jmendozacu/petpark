<?php

class Virtua_TLSoft_Model_Observer
{
    public function removeToken()
    {
        $session = Mage::getSingleton('core/session');
        $session->unsetData('barion_token');
    }
}