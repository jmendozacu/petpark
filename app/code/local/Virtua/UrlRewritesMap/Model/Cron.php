<?php

use Virtua_UrlRewritesMap_Helper_Data as Helper;

/**
 * Class Virtua_UrlRewritesMap_Model_Cron
 */
class Virtua_UrlRewritesMap_Model_Cron
{
    /**
     * Generate Url Rewrites Map if user update new file.
     */
    public function generateUrlRewritesMap()
    {
        if (Helper::checkIfTheFileHasBeenUpdated()) {
            Mage::getModel('Virtua_UrlRewritesMap_Model_Rewrites')->run();
            Helper::saveConfigIfUrlRewritesMapHasBeenGenerated();
        }
    }
}
