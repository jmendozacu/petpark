<?php

class Virtua_Inteo_Helper_Data extends Mage_Core_Helper_Abstract
{
    const INTEO_ORDERS_API_URL = 'https://eshops.inteo.sk/api/v1/incomingorders/';
    const URL_PATH_API_TOKEN = 'general/virtua/inteo_api_token';
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
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://eshops.inteo.sk/api/v1/incomingorders/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        $data = Mage::getModel('virtua_inteo/inteo')->getJsonData();
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
}
