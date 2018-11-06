<?php
/**
 * @category  TlSoft
 * @package   Virtua_TlSoft
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_TLSoft_Model_PaymentTypes
 */
class Virtua_TLSoft_Model_PaymentTypes
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'immediate',
                'label' => 'Immediate',
            ],
            [
                'value' => 'reservation',
                'label' => 'Reservation',
            ]
        ];
    }
}
