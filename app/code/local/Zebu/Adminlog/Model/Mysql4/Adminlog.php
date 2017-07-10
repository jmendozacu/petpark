<?php

class Zebu_Adminlog_Model_Mysql4_Adminlog extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the config_id refers to the key field in your database table.
        $this->_init('adminlog/adminlog', 'id');
    }
}