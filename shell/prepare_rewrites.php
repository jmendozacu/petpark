<?php

require_once 'abstract.php';

/**
 * Virtua Prepare Rewrites Generator Shell Script
 */
class Virtua_Shell_Prepare_Rewrites extends Mage_Shell_Abstract
{
    const REWRITES_CSV_FILE = 'rewrites.csv';
    const REWRITES_TXT_FILE = 'rewrites.txt';
    const CSV_DELIMETER = ';';
    const SLASH = '/';

    /**
     * Run script
     *
     */
    public function run()
    {
        $csvFile = $this->getFilePath(self::REWRITES_CSV_FILE);
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
            $rowArray = explode(self::CSV_DELIMETER, $row[0]);
            if (!isset($rowArray[0]) || !isset($rowArray[1])) {
                echo "Skipping the row \n";
                continue;
            }
            $redirectFrom = rtrim($rowArray[0], self::SLASH);
            $redirectTo = $rowArray[1];
            $fileContent .= "$redirectFrom $redirectTo\n";
        }

        $destinationFile = $this->getFilePath(self::REWRITES_TXT_FILE);
        if (file_exists($destinationFile)) {
            unlink($destinationFile);
        }

        try {
            $txtFileHandler = fopen($destinationFile, "w");
            fwrite($txtFileHandler, $fileContent);
            fclose($txtFileHandler);
            echo "Rewrites have been saved to " . $destinationFile;
        } catch (Exception $exception) {
            echo "Error occured: \n";
            echo $exception->getMessage();
        }

        echo PHP_EOL;
    }

    private function getFilePath($filename)
    {
        return Mage::getBaseDir() . DIRECTORY_SEPARATOR . $filename;
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
