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
        $model = Mage::getModel('urlrewritesmap/cleaner');
        if ($model) {
            try {
                $model->run();
            } catch (\Exception $exception) {
                echo $exception->getMessage() . PHP_EOL;
            }

        }
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f prepare_rewrites.php
USAGE;
    }
}

$shell = new Virtua_Shell_Clean_Rewrites();
$shell->run();
