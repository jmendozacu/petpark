<?php

require_once 'abstract.php';

/**
 * Virtua Rewrites Cleaner Shell Script
 */
class Virtua_Shell_Clean_Rewrites extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     * @return void
     */
    public function run()
    {
        try {
            /** @var \Virtua_UrlRewritesMap_Model_Cleaner $model */
            $model = Mage::getModel('urlrewritesmap/cleaner');
            $model->run();
        } catch (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f clean_rewrites.php
USAGE;
    }
}

$shell = new Virtua_Shell_Clean_Rewrites();
$shell->run();
