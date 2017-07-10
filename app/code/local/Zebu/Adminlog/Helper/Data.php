<?php

class Zebu_Adminlog_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isLicenseValid(){
      return Mage::helper('zebu_config')->isLicenseValid($this->_getModuleName());
    }
    
    public function isEnabled(){
        return $this->isLicenseValid() && Mage::getStoreConfig('zebu_adminlog/zebu_adminlog_general/zebu_adminlog_enabled');
    }
    
}