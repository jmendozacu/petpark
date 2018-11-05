<?php

class Virtua_TLSoft_Model_PaymentTypes
{
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