<?php
/**
 * @category  TlSoft
 * @package   Virtua_TlSoft
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_TLSoft_Block_Form
 */
class Virtua_TLSoft_Block_Form extends TLSoft_BarionPayment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('virtuatlsoft/form.phtml');
    }

    /**
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getBarionPaymentInstructions()
    {
        $instructions = Mage::getStoreConfig('payment/tlbarion/virtua_tlsoft_instructions', Mage::app()->getStore());
        echo $instructions;
    }
}
