<?php
/**
 * @category  BarionPayment
 * @package   Virtua_BarionPayment
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_BarionPayment_Block_Grid
 */
class Virtua_BarionPayment_Block_Grid extends TLSoft_BarionPayment_Block_Adminhtml_Barionpayment_Grid
{
    /**
     * @return TLSoft_BarionPayment_Block_Adminhtml_Barionpayment_Grid
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumn('real_orderid', array(
            'header'=> Mage::helper('tlbarion')->__('Payment Id'),
            'width' => '',
            'type'  => 'text',
            'index' => 'real_orderid',
        ));

        return $this;
    }
}
