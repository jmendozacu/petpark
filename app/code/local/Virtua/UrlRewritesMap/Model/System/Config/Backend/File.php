<?php

use Virtua_UrlRewritesMap_Helper_Data as Helper;

class Virtua_UrlRewritesMap_Model_System_Config_Backend_File extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if ($_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value']) {
            $uploadDir = $this->getUploadDir();

            $result = $this->manageUrlRewritesMapFile($uploadDir);

            $fileName = $result['file'];
            $this->manageScopesAndSaveFieldValueIfFileExist($fileName);
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
    public function manageScopesAndSaveFieldValueIfFileExist($fileName)
    {
        if ($fileName) {
            $this->prependScopeInfoIfItsPossible($fileName);
            $this->setValue($fileName);
        }
    }

    /**
     * @param string $fileName
     */
    public function prependScopeInfoIfItsPossible($fileName)
    {
        if ($this->addWhetherScopeInfo()) {
            $filename = $this->prependScopeInfo($fileName);
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
         * Add scope info
         */
        if (!empty($descendantOfNode['scope_info'])) {
            $uploadDir = $this->appendScopeInfo($uploadDir);
        }

        /**
         * Take root from config
         */
        if (!empty($descendantOfNode['config'])) {
            $uploadRoot = Mage::getBaseDir('media');
            $uploadDir = $uploadRoot . '/' . $uploadDir;
        }
        return $uploadDir;
    }

    /**
     * Prepend path with scope info
     *
     * E.g. 'stores/2/path' , 'websites/3/path', 'default/path'
     *
     * @param string $path
     *
     * @return string
     */
    protected function prependScopeInfo($path)
    {
        $scopeInfo = $this->getScope();
        if ('default' !== $this->getScope()) {
            $scopeInfo .= '/' . $this->getScopeId();
        }
        return $scopeInfo . '/' . $path;
    }

    /**
     * Add scope info to path
     *
     * E.g. 'path/stores/2' , 'path/websites/3', 'path/default'
     *
     * @param string $path
     *
     * @return string
     */
    protected function appendScopeInfo($path)
    {
        $path .= '/' . $this->getScope();
        if ('default' !== $this->getScope()) {
            $path .= '/' . $this->getScopeId();
        }
        return $path;
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return bool
     */
    protected function addWhetherScopeInfo()
    {
        $fieldConfig = $this->getFieldConfig();
        $descendantOfNode = $fieldConfig->descend('upload_dir');
        return !empty($descendantOfNode['scope_info']);
    }
}
