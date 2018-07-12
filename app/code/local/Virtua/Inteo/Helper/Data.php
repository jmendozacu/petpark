<?php

class Virtua_Inteo_Helper_Data extends Mage_Core_Helper_Abstract
{
    const INTEO_ORDERS_API_URL = 'https://eshops.inteo.sk/api/v1/incomingorders/';
    const URL_PATH_API_TOKEN = 'general/virtua/inteo_api_token';
    const URL_PATH_LAST_TRANSFERRED_ORDER_DATE = 'general/virtua/inteo_last_transferred_order_date';
    const OMEGA_RESPONSE_LOG_FILE = 'omega_response.log';

    /**
     * @return mixed
     */
    public function getApiToken()
    {
        return Mage::getStoreConfig(self::URL_PATH_API_TOKEN);
    }

    /**
     * Transfer orders to Omega app
     * @return bool
     */
    public function transferData()
    {
        $apiToken = $this->getApiToken();
        if (!$apiToken) {
            $this->logOmegaResponse('No API Token found.');
            return false;
        }

        $data = Mage::getModel('virtua_inteo/inteo')->getJsonData();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://eshops.inteo.sk/api/v1/incomingorders/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->getApiToken()
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        if (isset($response['result']) && $response['result']) {
            return true;
        }
        // save response in the log file if result is not true
        $this->logOmegaResponse($response);
        return false;
    }

    public function logOmegaResponse($response)
    {
        Mage::log($response, null, self::OMEGA_RESPONSE_LOG_FILE);
    }

    public function getLastTransferredOrderDate()
    {
        return Mage::getStoreConfig(self::URL_PATH_LAST_TRANSFERRED_ORDER_DATE);
    }

    public function setLastTransferredOrderDate()
    {
        $date = $this->getCurrentDate();
        Mage::getConfig()->saveConfig(self::URL_PATH_LAST_TRANSFERRED_ORDER_DATE, $date, 'default', 0);
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    public function getCurrentDate()
    {
        return date(DATE_ISO8601);
    }
}
