<?php

class Virtua_Seo_ImagemapController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $model = Mage::getModel('virtua/imagesitemap');
        $model->generateImageSitemap();
    }
}
