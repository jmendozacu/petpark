<?php

class Virtua_Inteo_Helper_Data extends Mage_Core_Helper_Abstract
{
    const INTEO_ORDERS_API_URL = 'https://eshops.inteo.sk/api/v1/incomingorders/';
    const API_TOKEN = '7cc990c4-76c5-4195-8e60-4a2420d8a87d';

    public function createApiConnection()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://eshops.inteo.sk/api/v1/incomingorders/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        $data = Mage::getModel('virtua_inteo/inteo')->getJsonData();
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


        //TODO get authorization token from dbase
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . self::API_TOKEN
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        \Zend_Debug::dump($response);
    }
}
