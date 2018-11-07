<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Block_Form
 */
class Virtua_BarionPayment_Block_Form extends TLSoft_BarionPayment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('virtua/barionpayment/form.phtml');
    }

    public function getBarionPaymentInstructions()
    {
        return Mage::getStoreConfig('payment/tlbarion/virtua_barionpayment_instructions', Mage::app()->getStore());
    }
}
