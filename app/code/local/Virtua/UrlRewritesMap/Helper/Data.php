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

    const MEDIA_MAIN_DIR = 'rewrite';

    /**
     * Get path to URL rewrite map file
     *
     * @param string $store
     *
     * @return null|string
     */
    public function getUrlRewritesMapFilePath($store = 'sk')
    {
        return ($store === 'sk') ?
            Mage::getStoreConfig(self::XML_PATH_URL_REWRITES_MAP_SK) :
            Mage::getStoreConfig(self::XML_PATH_URL_REWRITES_MAP_CZ);
    }


    /**
     * Get URL of download rewrite map file
     *
     * @param string $store
     *
     * @return string
     */
    public function getDownloadRewritesMapFileUrl($store = 'sk')
    {
        if (!$this->getUrlRewritesMapFilePath($store)) {
            return '';
        }
        return Mage::getBaseUrl('media') . 'rewrite' . DS . $this->getUrlRewritesMapFilePath($store);
    }
}