<?php

class Zebu_Adminlog_Model_Adminlog extends Mage_Core_Model_Abstract {

    public function _construct() {
      parent::_construct();
      $this->_init('adminlog/adminlog');
    }


    public function logAction($userId, $controller, $action, $subject){

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');//
        $data = new Zebu_Adminlog_Model_Mysql4_Adminlog();
        $helper = Mage::helper('core/http');
        
        $connection->insertMultiple($data->getTable('adminlog/adminlog') ,array(
          'user_id'               => $userId,
          'controller'            => $controller,
          'action'                => $action,
          'subject'               => $subject,
          'server_addr'           => /*long2ip*/($helper->getServerAddr(true)),
          'remote_addr'           => /*long2ip*/($helper->getRemoteAddr(true)),
          'http_user_agent'       => $helper->getHttpUserAgent(true)
        ));
    }
    
    public function clearLogs($days){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');//
        $data = new Zebu_Adminlog_Model_Mysql4_Adminlog();
        $helper = Mage::helper('core/http');
        
//        Mage::log('DELETE FROM '.$data->getTable('adminlog/adminlog').' WHERE `access_date`< DATE_SUB(NOW(), INTERVAL '.$days.' DAY)');
        $result = $connection->query('DELETE FROM '.$data->getTable('adminlog/adminlog').' WHERE `access_date`< DATE_SUB(NOW(), INTERVAL '.$days.' DAY)');
        
    }
  
}