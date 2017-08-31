<?php

class Virtua_Seoattributes_Model_Seoattributes extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('virtua_seoattributes/seoattributes');
    }

    public function getSeoData($storeId = 0)
    {
        $seoData = array();
        $params = Mage::app()->getRequest()->getParams();
        Mage::log(print_r($params, true));
        $seoAttributesHelper = Mage::helper('virtua_seoattributes');
        $categoryId = Mage::app()->getRequest()->getParam('id');
        if ($categoryId) {
            $includedParams = $seoAttributesHelper->parseAttributes($params);
            if (!empty($includedParams)) {
                //$seoData = $this->getResource()->getSeoDataByIdAndParams($categoryId, $includedParams, true);
                //if (empty($seoData)) {
                    $seoData = $this->getResource()->getSeoDataByIdAndParams($categoryId, $includedParams, false);
                //}
            }
        }
        return $seoData;
    }
}
