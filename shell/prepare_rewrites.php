<?php

require_once 'abstract.php';

/**
 * Virtua Prepare Rewrites Generator Shell Script
 */
class Virtua_Shell_Prepare_Rewrites extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        $model = Mage::getModel('urlrewritesmap/rewrites');
        if ($model) {
            $model->run();
        }
        return;
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f prepare_rewrites.php
USAGE;
    }
}

$shell = new Virtua_Shell_Prepare_Rewrites();
$shell->run();
