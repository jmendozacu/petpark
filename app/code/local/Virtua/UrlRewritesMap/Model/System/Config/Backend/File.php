<?php
/**
 * @category  UrlRewritesMap
 * @package   Virtua_UrlRewritesMap
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

use Virtua_UrlRewritesMap_Helper_Data as Helper;

/**
 * Class Virtua_UrlRewritesMap_Model_System_Config_Backend_File
 */
class Virtua_UrlRewritesMap_Model_System_Config_Backend_File extends Mage_Core_Model_Config_Data
{
    /**
     * Save uploaded file before saving config value
     *
     * @return $this|Mage_Core_Model_Abstract
     *
     * @throws Mage_Core_Exception
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if ($_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value']) {
            $uploadDir = $this->getUploadDir();

            $result = $this->manageUrlRewritesMapFile($uploadDir);

            $fileName = $result['file'];
            $this->saveFieldValueIfFileExist($fileName);
        } elseif (is_array($value) && !empty($value['delete'])) {
            $this->delete();
            $this->_dataSaveAllowed = false;
        } else {
            $this->unsValue();
        }

        return $this;
    }

    /**
     * @param string $uploadDir
     *
     * @return $this|bool|void
     *
     * @throws Mage_Core_Exception
     */
    public function manageUrlRewritesMapFile($uploadDir)
    {
        try {
            Helper::saveConfigIfUrlRewritesMapFileEdited();
            $file = [];

            $tmpName = $_FILES['groups']['tmp_name'];
            $file['tmp_name'] = $tmpName[$this->getGroupId()]['fields'][$this->getField()]['value'];

            $name = $_FILES['groups']['name'];
            $file['name'] = $name[$this->getGroupId()]['fields'][$this->getField()]['value'];

            $uploader = new Mage_Core_Model_File_Uploader($file);
            $uploader->setAllowedExtensions();
            $uploader->setAllowRenameFiles(true);

            return $uploader->save($uploadDir);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());

            return $this;
        }
    }

    /**
     * @param string $fileName
     */
    public function saveFieldValueIfFileExist($fileName)
    {
        if ($fileName) {
            $this->setValue($fileName);
        }
    }

    /**
     * Return path to directory for upload file
     *
     * @return string
     *
     * @throws Mage_Core_Exception
     */
    protected function getUploadDir()
    {
        $fieldConfig = $this->getFieldConfig();

        if (empty($fieldConfig->upload_dir)) {
            Mage::throwException(Mage::helper('catalog')->__('The base directory to upload file is not specified.'));
        }

        $uploadDir = (string)$fieldConfig->upload_dir;
        $descendantOfNode = $fieldConfig->descend('upload_dir');

        /**
         * Take root from config
         */
        if (!empty($descendantOfNode['config'])) {
            $uploadRoot = Mage::getBaseDir('media');
            $uploadDir = $uploadRoot . '/' . $uploadDir;
        }

        return $uploadDir;
    }
}
