<?php

require_once 'abstract.php';

/**
 * Virtua Iamgemap Generator Shell Script
 */
class Virtua_Shell_GenerateImagemap extends Mage_Shell_Abstract
{
     /**
     * Run script
     *
     */
    public function run()
    {
        $model = Mage::getModel('virtua/imagesitemap');
        $sitemaps = $model->getSitemaps();
        try {
            foreach ($sitemaps as $sitemap) {
                $model->generateImageSitemap($sitemap);
                echo "Imagemap for " . $sitemap['version'] . " store has been created \n";
            }
        } catch(Exception $exc) {
            Mage::log($exc->getMessage());
        }
    }
}

$shell = new Virtua_Shell_GenerateImagemap();
$shell->run();
