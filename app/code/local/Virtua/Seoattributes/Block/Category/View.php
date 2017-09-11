<?php

class Virtua_Seoattributes_Block_Category_View extends Mage_Catalog_Block_Category_View
{

    protected $seoData;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $category = $this->getCurrentCategory();
            if ($this->helper('catalog/category')->canUseCanonicalTag()) {
                $rewrite = Mage::getStoreConfig('web/seo/use_rewrites',Mage::app()->getStore()->getId());
                // Rewrite
                if($rewrite == 1) {
                    $headBlock->removeItem('link_rel',$category->getUrl());
                    $headBlock->addLinkRel('canonical', Mage::getModel('filterurls/catalog_layer_filter_item')->getSpeakingFilterUrl(FALSE, TRUE, array(), true));
                }
                else{
                    $headBlock->addLinkRel('canonical', $category->getUrl());
                }
            }
            $seoData = $this->getSeoData();
            if (!empty($seoData)) {
                $headBlock->setTitle($seoData['title']);
                $headBlock->setDescription($seoData['meta_description']);
            }
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
