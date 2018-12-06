<?php

use Virtua_UrlRewritesMap_Helper_Data as Helper;

/**
 * Class Virtua_UrlRewritesMap_Model_Cleaner
 */
class Virtua_UrlRewritesMap_Model_Cleaner
{
    const SPACE_BREAK = ' ';
    const ITEMS_PER_PAGE = 100;

    /**
     * Execute script method
     *
     * @return void
     * @throws Exception
     */
    public function run()
    {
        $rewritesFile = $this->getFilePath(Helper::REWRITES_TXT_FILE);
        if (!\file_exists($rewritesFile)) {
            throw new \Exception($rewritesFile . " not exists!");
        }

        $requestPaths = [];
        $txtLines = \file($rewritesFile, FILE_IGNORE_NEW_LINES);
        foreach ($txtLines as $line) {

            $lineAsArray = \explode(self::SPACE_BREAK, $line);
            if (\count($lineAsArray) !== 2 || false === \is_string($lineAsArray[0])) {
                continue;
            }

            $requestPath = $lineAsArray[0];
            $requestPaths[] = $requestPath;
        }

        unset($txtLines);

        do {
            $requestPathsPart = \array_splice(
                $requestPaths, 0, self::ITEMS_PER_PAGE
            );
            $itemsCount = \count($requestPathsPart);

            $rewritesToRemove = $this->getUrlRewriteCollectionByRequestPaths($requestPathsPart);
            $this->removeRewrites($rewritesToRemove);
        } while ($itemsCount === self::ITEMS_PER_PAGE);
    }

    /**
     * Retrieve rewrites collection by given request paths
     *
     * @param array $requestPaths
     *
     * @return Mage_Core_Model_Url_Rewrite[]
     */
    private function getUrlRewriteCollectionByRequestPaths(array $requestPaths)
    {
        /** @var \Mage_Core_Model_Resource_Url_Rewrite_Collection $collection */
        $collection = Mage::getResourceModel('core/url_rewrite_collection');
        $collection->addFieldToFilter('request_path', ['in', $requestPaths]);

        return $collection->getItems();
    }

    /**
     * Remove URL rewrites
     *
     * @param array $items
     *
     * @return void
     */
    private function removeRewrites(array $items)
    {
        try {
            foreach ($items as $item) {
                /** @var \Mage_Core_Model_Url_Rewrite $item */
                $this->printMessage('Removing ' . $item->getRequestPath());
                $item->delete();
            }
        } catch (\Exception $exception) {
            $this->printMessage($exception->getMessage());
        }
    }

    /**
     * Get file path of given filename
     *
     * @param string $filename
     * @param string $type
     *
     * @return string
     */
    private function getFilePath($filename, $type = 'base')
    {
        return Mage::getBaseDir($type) . DIRECTORY_SEPARATOR . Helper::MEDIA_MAIN_DIR . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Print message in CLI
     *
     * @param string $message
     *
     * @return void
     */
    private function printMessage($message)
    {
        echo $message . PHP_EOL;
    }
}