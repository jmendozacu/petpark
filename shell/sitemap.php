<?php

require_once 'abstract.php';

/**
 * Virtua Sitemap Generator Shell Script
 */
class Virtua_Shell_Sitemap extends Mage_Shell_Abstract
{

    const STORE_SK = '1';
    const STORE_CZ = '2';
    const STORE_ALL = 'all';

    protected $possibleStoreValues = array(self::STORE_SK, self::STORE_CZ, self::STORE_ALL);

     /**
     * Run script
     *
     */
    public function run()
    {
        if (isset($this->_args['store'])) {
            $store = $this->_args['store'];
            if (!in_array($store, $this->possibleStoreValues)) {
                echo 'Wrong store value. Possible values: ' . implode(', ', $this->possibleStoreValues);
                echo PHP_EOL;
                return;
            }
            $collection = Mage::getModel('sitemap/sitemap')->getCollection();
            if ($store != self::STORE_ALL) {
                $collection->addFieldToFilter('store_id', $store);
            }
            foreach ($collection as $sitemap) {
                try {
                    $sitemap->generateXml();
                    echo 'Sitemap ' . $sitemap->getSitemapFilename() . ' has been generated.' . PHP_EOL;
                }
                catch (Exception $e) {
                    Mage::log($e->getMessage());
                }
            }
        } else {
            echo $this->usageHelp();
        }
        echo PHP_EOL;
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f sitemap.php -- [options]

  --store                       Store version (1, 2 or all)
  help                          This help

USAGE;
    }
}

$shell = new Virtua_Shell_Sitemap();
$shell->run();
