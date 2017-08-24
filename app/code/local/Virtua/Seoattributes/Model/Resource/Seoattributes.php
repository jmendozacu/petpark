<?php

class Virtua_Seoattributes_Model_Resource_Seoattributes extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('virtua_seoattributes/seoattributes', 'entity_id');
    }

    public function getSeoDataByIdAndParams($categoryId, $params, $predefined = false)
    {
        $paramValues = array();
        if (!$predefined) {
            foreach ($params as $attrCode => $optionId) {
                $paramValues['{{' . $attrCode . '}}'] = $this->getOptionValueByOptionId($attrCode, $optionId);
                $params[$attrCode] = "all";
            }
        }
        $params = json_encode($params);
        //Mage::log($params);
        $storeId = Mage::app()->getStore()->getId();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "
            SELECT meta_title, meta_description, title  
            FROM virtua_seoattributes 
            WHERE attributes = '".$params."' AND category_id = '".$categoryId."' AND enabled='1' AND store_id = '".$storeId."' 
        ";
        $result = $readConnection->fetchAll($query);
        if (!empty($result)) {
            $helper = Mage::helper('virtua_seoattributes');
            $result = $result[0];
            foreach ($result as $key => $value) {
                $result[$key] = $helper->replaceVariables($value, $paramValues);
            }
            return $result;
        }
        return array();
    }

    public function getOptionValueByOptionId($attrCode, $optionId)
    {
        $attributeInfo = Mage::getModel('eav/entity_attribute')
            ->loadByCode('catalog_product', $attrCode);

        if ($attributeInfo->getFrontendInput() == 'select') {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = "
            SELECT o.value 
            FROM eav_attribute_option_value AS o 
            WHERE o.option_id = '".$optionId."' AND o.store_id='0'
        ";
            $result = $readConnection->fetchAll($query);
            if (!empty($result)) {
                return $result[0]['value'];
            }
        } else {
            return '';
        }
    }
}
