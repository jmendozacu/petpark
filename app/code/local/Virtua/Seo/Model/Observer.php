<?php

class Virtua_Seo_Model_Observer
{
    public function generateImageMap()
    {
        $model = Mage::getModel('virtua/imagesitemap');
        $sitemaps = $model->getSitemaps();
        try {
            foreach ($sitemaps as $sitemap) {
                $model->generateImageSitemap($sitemap);
            }
        } catch(Exception $exc) {
            Mage::log($exc->getMessage());
        }
    }
}