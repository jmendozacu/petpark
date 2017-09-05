<?php

class Virtua_Seoattributes_Model_Resource_Seoattributes extends Mage_Core_Model_Mysql4_Abstract
{
    const DOMAIN_SK = 'petpark.sk';
    const DOMAIN_CZ = 'pet-park.cz';

    protected $_storeId;
    protected $_flague = true;

    protected function _construct()
    {
        $this->_init('virtua_seoattributes/seoattributes', 'entity_id');
    }

    public function getCurrentStoreId()
    {
        if (!$this->_storeId) {
            $this->_storeId = Mage::app()->getStore()->getId();
        }
        return $this->_storeId;
    }

    public function getSeoDataByIdAndParams($categoryId, $params)
    {
        if (count($params)) {
            return $this->prepareAndGetSeoDataFromMultipleParams($categoryId, $params);
        }
        return array();
    }

    /**
     * Manufacturer has to be always on the first place, color on the last.
     * @param array $params
     * @return array
     */
    protected function _sortParams($params)
    {
        if (!empty($params)) {
            if (key_exists('manufacturer', $params)) {
                $temp['manufacturer'] = $params['manufacturer'];
                unset($params['manufacturer']);
                $params = array_merge($temp, $params);
                $temp = null;
            }
            if (key_exists('farba_hurtta', $params)) {
                $farbaHurtta = $params['farba_hurtta'];
                unset($params['farba_hurtta']);
                $params['farba_hurtta'] = $farbaHurtta;
                $farbaHurtta = null;
            }
            return $params;
        }
    }

    public function prepareAndGetSeoDataFromMultipleParams($categoryId, $params)
    {
        $storeId = $this->getCurrentStoreId();
        $categoryTitle = $this->getCategoryTitle($categoryId);
        $paramsString = '';
        $params = $this->_sortParams($params);
        Mage::log(print_r($params, true));
        foreach ($params as $attrCode => $optionId) {
            //Mage::log($this->getOptionValueByOptionId($attrCode, $optionId));
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
        //Mage::log(print_r($insertData, true));
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
        $domain = $this->_getCurrentDomain();
        if ($this->getCurrentStoreId() == 1) {
            $out = 'Ponúkame vám ' . $categoryTitle . ' ' . $paramsString . ' na stránkach ' . $domain . '. Vyberte si produkty, ktoré potešia vašich domácich miláčikov.';
        } else {
            $out = 'Nabízíme vám ' . $categoryTitle . ' ' . $paramsString . ' na stránkach ' . $domain . '. Vyberte si produkty, které potěší vaše domácí mazlíčky.';
        }
        return $out;
    }

    protected function _getCurrentDomain()
    {
        return ($this->getCurrentStoreId() == 1) ? self::DOMAIN_SK : self::DOMAIN_CZ;
    }

    protected function _prepareTitle($categoryTitle, $paramsString, $meta = false)
    {
        $domain = $this->_getCurrentDomain();
        $out = $categoryTitle . ' ' . $paramsString;
        if ($meta) {
            $out .= ' | ' . $domain;
        }
        return $out;
    }

    public function getCategoryTitle($categoryId)
    {
        $model = Mage::getModel('catalog/category');
        $category = $model->setStoreId($this->getCurrentStoreId())->load($categoryId);
        if ($category) {
            return ($category->getNameSeo()) ? $category->getNameSeo() : $category->getName();
        }
        return '';
    }

    public function getOptionValueByOptionId($attrCode, $optionId, $adminStore = false)
    {
        $attributeInfo = Mage::getModel('eav/entity_attribute')
            ->loadByCode('catalog_product', $attrCode);

        $storeId = ($adminStore) ? 0 : $this->getCurrentStoreId();
        $allowedInputTypes = array('select', 'multiselect');
        if (in_array($attributeInfo->getFrontendInput(), $allowedInputTypes)) {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = "
            SELECT o.value 
            FROM eav_attribute_option_value AS o 
            WHERE o.option_id = '".$optionId."' AND o.store_id='".$storeId."'
        ";
            $result = $readConnection->fetchAll($query);
            if (!empty($result)) {
                return $result[0]['value'];
            } elseif ($this->_flague && $attrCode == 'manufacturer') {
                $out = $this->getOptionValueByOptionId($attrCode, $optionId, true);
                $this->_flague = false;
                return $out;
            }
        } else {
            return '';
        }
    }
}
