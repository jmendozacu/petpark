<?php

class Virtua_Seo_ImagemapController extends Mage_Core_Controller_Front_Action
{
    /**
     * TODO remove controller before final deployment
     */
    public function indexAction()
    {
        $model = Mage::getModel('virtua/imagesitemap');
        $sitemaps = $model->getSitemaps();
        foreach ($sitemaps as $sitemap) {
            $model->generateImageSitemap($sitemap);
        }
    }
}
