<?php

class Zebu_Config_Helper_Data extends Mage_Core_Helper_Abstract {


    const SINGLE_KEY_LENGTH = 8; 
    const KEY_LENGTH = 32;
        
    public function isLicenseValid($moduleName){
        
        $isLicenseValid = true;

        if (Mage::app()->getStore()->isAdmin()) {
        
          $namespace = $moduleName.'_check';
          if (!Mage::getSingleton('core/session')->getData($namespace)){
  
            $old = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 1);
            $helper = Mage::helper('core/http');
            $url  = 'http://magentomoduly.zebu.cz/check';
            $url .= 
              '?module='.$moduleName.
              '&remote_addr='.$helper->getRemoteAddr(true).
              '&remote_name='.gethostbyaddr(long2ip($helper->getRemoteAddr(true))).
              '&server_addr='.$helper->getServerAddr(true).
              '&server_name='.$helper->getHttpHost(true).
              '&is_license_valid='.((int)$isLicenseValid);
              
            if (@fopen(($url), 'r'))
              Mage::getSingleton('core/session')->setData($namespace,'checked');
                                  
            ini_set('default_socket_timeout', $old);
          }
        } 
        
        return $isLicenseValid;
    }

    private function getKey($moduleName,$url){
        return '';
    }

}