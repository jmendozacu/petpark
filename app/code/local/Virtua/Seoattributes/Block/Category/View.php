<?php

class Virtua_Seoattributes_Block_Category_View extends Mage_Catalog_Block_Category_View
{

    protected $seoData;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $seoData = $this->getSeoData();
        if (!empty($seoData)) {
            $this->getLayout()->getBlock('head')->setTitle($seoData['title']);
            $this->getLayout()->getBlock('head')->setDescription($seoData['meta_description']);
        }

        return $this;
    }

    public function getSeoData()
    {
        if (!$this->seoData) {
            $seoAttributesModel = Mage::getModel('virtua_seoattributes/seoattributes');
            $this->seoData = $seoAttributesModel->getSeoData();
        }
        return $this->seoData;
    }
}
