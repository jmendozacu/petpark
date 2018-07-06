<?php

use Virtua_UrlRewritesMap_Helper_Data as Helper;

class Virtua_UrlRewritesMap_Model_Rewrites
{
    /**
     * Run script
     *
     */
    public function run()
    {
        $helper = Mage::helper('urlrewritesmap');
        $csvFile = $this->getFilePath($helper->getUrlRewritesMapFilePath(), Mage_Core_Model_Store::URL_TYPE_MEDIA);
        if (!file_exists($csvFile)) {
            echo $csvFile . " not exists!";
        }

        $csv = array_map('str_getcsv', file($csvFile));
        $fileContent = '';

        foreach ($csv as $row) {
            if (!isset($row[0])) {
                echo "Row is not set \n";
                continue;
            }
            $rowArray = explode(Helper::CSV_DELIMETER, $row[0]);
            if (!isset($rowArray[0]) || !isset($rowArray[1])) {
                echo "Skipping the row \n";
                continue;
            }
            $redirectFrom = rtrim($this->removeUrlBase($rowArray[0]), Helper::SLASH);
            $redirectTo = $this->prepareDestinationUrl($this->removeUrlBase($rowArray[1]));
            echo $redirectTo . PHP_EOL;
            $fileContent .= "$redirectFrom $redirectTo\n";
        }

        $destinationFile = $this->getFilePath(Helper::REWRITES_TXT_FILE);
        if (file_exists($destinationFile)) {
            //unlink($destinationFile);
        }

        try {
//            $txtFileHandler = fopen($destinationFile, "w");
//            fwrite($txtFileHandler, $fileContent);
//            fclose($txtFileHandler);
            echo "Rewrites have been saved to " . $destinationFile;
        } catch (Exception $exception) {
            echo "Error occured: \n";
            echo $exception->getMessage();
        }

        echo PHP_EOL;
    }

    private function removeUrlBase($url)
    {
        $base = 'https://www.petpark.sk/';
        return str_replace($base, '', $url);
    }

    private function prepareDestinationUrl($url)
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, 'https://www.petpark.sk/' . $url);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle,  CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);

        $response = curl_exec($handle);

        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        if($httpCode !== Helper::HTTP_CODE_SUCCESS) {
            return Helper::SLASH;
        }

        if (preg_match('~Location: (.*)~i', $response, $match)) {
            $location = trim($match[1]);
            return ltrim($location, Helper::SLASH);
        }

        return $url;
    }

    private function getFilePath($filename, $type = 'base')
    {
        return Mage::getBaseDir($type) . DIRECTORY_SEPARATOR . Helper::MEDIA_MAIN_DIR . DIRECTORY_SEPARATOR . $filename;
    }
}