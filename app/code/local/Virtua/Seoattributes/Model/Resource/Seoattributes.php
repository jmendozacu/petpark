<?php

class Virtua_Seoattributes_Model_Resource_Seoattributes extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('virtua_seoattributes/seoattributes', 'entity_id');
    }

    public function getSeoDataByIdAndParams($categoryId, $params, $predefined = false)
    {
//        $paramValues = array();
//        if (!$predefined) {
//            foreach ($params as $attrCode => $optionId) {
//                $paramValues['{{' . $attrCode . '}}'] = $this->getOptionValueByOptionId($attrCode, $optionId);
//                $params[$attrCode] = "all";
//            }
//        }
//        $paramsJson = json_encode($params);
        //Mage::log($params);
        $storeId = Mage::app()->getStore()->getId();
//        $resource = Mage::getSingleton('core/resource');
//        $readConnection = $resource->getConnection('core_read');
//        $query = "
//            SELECT meta_title, meta_description, title
//            FROM virtua_seoattributes
//            WHERE attributes = '".$paramsJson."' AND category_id = '".$categoryId."' AND enabled='1' AND store_id = '".$storeId."'
//        ";
//        $result = $readConnection->fetchAll($query);
//        if (!empty($result)) {
//            $helper = Mage::helper('virtua_seoattributes');
//            $result = $result[0];
//            foreach ($result as $key => $value) {
//                $result[$key] = $helper->replaceVariables($value, $paramValues);
//            }
//            return $result;
//        } else if (count($params) > 1) {
//            return $this->prepareAndGetSeoDataFromMultipleParams($categoryId, $params, $storeId);
//        }
        if (count($params)) {
            return $this->prepareAndGetSeoDataFromMultipleParams($categoryId, $params, $storeId);
        }
        return array();
    }

    public function prepareAndGetSeoDataFromMultipleParams($categoryId, $params, $storeId)
    {
        // enabled for sk store only
        if ($storeId != 1) {
            return array();
        }
        $categoryTitle = $this->getCategoryTitle($categoryId);
        $paramsString = '';
        foreach ($params as $attrCode => $optionId) {
            Mage::log($this->getOptionValueByOptionId($attrCode, $optionId));
            $optionValue = $this->getOptionValueByOptionId($attrCode, $optionId);
            if ($optionValue) {
                $paramsString .= $optionValue . '-';
            }
        }
        if ($paramsString != '') {
            $paramsString = rtrim($paramsString, '-');
        }
        $insertData = array(
            'category_id' => $categoryId,
            'attributes' => json_encode($params),
            'meta_title' => $this->_prepareTitle($categoryTitle, $paramsString, true),
            'title' => $this->_prepareTitle($categoryTitle, $paramsString),
            'meta_description' => $this->_prepareDescription($categoryTitle, $paramsString),
            'store_id' => $storeId,
            'enabled' => 1,
        );
        Mage::log(print_r($insertData, true));
        try {
            //$this->getModel()->setData($insertData);
            return $insertData;
        } catch (Exception $exception) {
            Mage::log($exception);
        }
        return array();
    }

    protected function _prepareDescription($categoryTitle, $paramsString)
    {
        $out = 'Ponúkame vám ' . $categoryTitle . ' ' . $paramsString . ' na stránkach petpark.sk. Vyberte si produkty, ktoré potešia vašich domácich miláčikov.';
        return $out;
    }

    protected function _prepareTitle($categoryTitle, $paramsString, $meta = false)
    {
        $out = $categoryTitle . ' ' . $paramsString;
        if ($meta) {
            $out .= ' | petpark.sk';
        }
        return $out;
    }

    public function getCategoryTitle($categoryId)
    {
        $model = Mage::getModel('catalog/category');
        $category = $model->load($categoryId);
        if ($category) {
            return ($category->getNameSeo()) ? $category->getNameSeo() : $category->getName();
        }
        return '';
    }

    public function getOptionValueByOptionId($attrCode, $optionId)
    {
        $attributeInfo = Mage::getModel('eav/entity_attribute')
            ->loadByCode('catalog_product', $attrCode);

        $allowedInputTypes = array('select', 'multiselect');
        if (in_array($attributeInfo->getFrontendInput(), $allowedInputTypes)) {
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
