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

    const FILE_FIELD_NAME = 'url_rewrites_map_file';
    const FIELD_GROUP_ID = 'virtua_urlrewritesmap';
    const FILE_CONFIG_PATH = 'virtua_urlrewritesmap/url_rewrites_map_file';

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

    /**
     * Saves config value if Url Rewrites Map file has been updated.
     *
     * @return void
     */
    public function saveConfigIfUrlRewritesMapFileEdited()
    {
        Mage::getConfig()
            ->removeCache()
            ->saveConfig(self::FILE_CONFIG_PATH, 1, 'default', 0);
    }

    /**
     * Change config data to '0' to make sure a rewrites map has been generated.
     *
     * @return void
     */
    public function saveConfigIfUrlRewritesMapHasBeenGenerated()
    {
        try {
            Mage::getConfig()
                ->removeCache()
                ->saveConfig(self::FILE_CONFIG_PATH, 0, 'default', 0);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Checks if Url Rewrites Map file has been updated.
     *
     * @return bool
     */
    public function checkIfTheFileHasBeenUpdated()
    {
        return (bool)Mage::getStoreConfig(self::FILE_CONFIG_PATH);
    }

    /**
     * Creates rewrites directory if it doesnt exist.
     *
     * @param string $fileName
     */
    public function createDirectoryIfItDoesntExist($fileName)
    {
        $varienIoFile = new Varien_Io_File();
        $dir = $varienIoFile->dirname($fileName);

        if (!$varienIoFile->fileExists($dir, false)) {
            $varienIoFile->mkdir($dir);
        }
    }
}
