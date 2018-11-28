<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Model_Observer
 */
class Virtua_BarionPayment_Model_Observer
{
    public function removeToken()
    {
        Mage::getSingleton('core/session')->unsetData('barion_token');
        Mage::getSingleton('core/session')->unsetData('prepared_barion_token');
    }
}
