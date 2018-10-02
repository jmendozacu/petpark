<?php

class Virtua_Seoattributes_Model_Resource_Seoattributes_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('virtua_seoattributes/seoattributes');
    }
}
