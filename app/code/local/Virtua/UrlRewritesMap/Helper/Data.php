<?php

class Virtua_UrlRewritesMap_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_URL_REWRITES_MAP_SK = 'general/virtua_urlrewritesmap/url_rewrites_map_file';
    const XML_PATH_URL_REWRITES_MAP_CZ = 'general/virtua_urlrewritesmap/url_rewrites_map_file_cz';

    const REWRITES_CSV_FILE = 'rewrites.csv';
    const REWRITES_TXT_FILE = 'rewrites.txt';
    const CSV_DELIMETER = ';';
    const SLASH = '/';
    const HTTP_CODE_SUCCESS = 200;

    const STORE_DOMAIN_SK = 'www.petpark.sk/';
    const STORE_DOMAIN_CZ = 'www.pet-park.cz/';

    const MEDIA_MAIN_DIR = 'rewrite';

    private $currentStore;

    private $stores = [
        self::STORE_DOMAIN_SK,
        self::STORE_DOMAIN_CZ,
    ];


    public function getUrlRewritesMapFilePath($store = 'sk')
    {
        return ($store === 'sk') ?
            Mage::getStoreConfig(self::XML_PATH_URL_REWRITES_MAP_SK) :
            Mage::getStoreConfig(self::XML_PATH_URL_REWRITES_MAP_CZ);
    }

    public function getDownloadRewritesMapFileUrl($store = 'sk')
    {
        if (!$this->getUrlRewritesMapFilePath($store)) {
            return '';
        }
        return Mage::getBaseUrl('media') . 'rewrite' . DS . $this->getUrlRewritesMapFilePath($store);
    }
}