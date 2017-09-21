<?php

class Virtua_Seo_Model_Imagesitemap extends Mage_Core_Model_Abstract
{
    const MAP_EXTENSION = '.xml';
    const MAP_BASE_NAME = 'imagemap_';

    protected $_storeVersion;
    protected $_storeId;

    protected $_enabledExtensions = array(
        'jpg', 'jpeg', 'png', 'tif', 'gif',
    );

    protected $_sitemaps = array(
        array(
            'version' => 'sk',
            'store_id' => '1',
            'file' => 'sitemap.xml',
        ),
        array(
            'version' => 'cz',
            'store_id' => '2',
            'file' => 'sitemapcz/sitemap.xml',
        ),
    );

    public function getSitemaps()
    {
        return $this->_sitemaps;
    }

    protected $_filePath;

    /**
     * Return real file path
     *
     * @return string
     */
    protected function _getFilePath()
    {
        if (is_null($this->_filePath)) {
            $this->_filePath = str_replace('//', '/', Mage::getBaseDir());
        }
        return $this->_filePath;
    }

    /**
     * Return file name of image map
     * @return string
     */
    protected function _getFileName()
    {
        $fileName = self::MAP_BASE_NAME . $this->_storeVersion . self::MAP_EXTENSION;
        return $fileName;
    }

    /**
     * Return full path to image map file
     * @return string
     */
    protected function _getRealFilePath()
    {
        $io = new Varien_Io_File();
        return $io->getCleanPath($this->_getFilePath() . DS . $this->_getFileName());
    }

    /**
     * Retrieve array of urls from given sitemap
     * @param $sitemap
     * @return array
     */
    protected function _getSiteUrls($sitemap)
    {
        $array = array();
        if ($sitemap) {
            foreach ($sitemap as $item) {
                if (!$item->loc) {
                    continue;
                }
                $loc = (array) $item->loc;
                $array[] = $loc[0];
            }
        }
        return $array;
    }

    /**
     * Generate image sitemap
     * @param array $sitemapData
     */
    public function generateImageSitemap($sitemapData)
    {
        $this->_storeVersion = $sitemapData['version'];
        $this->_storeId = $sitemapData['store_id'];
        $io = new Varien_Io_File();

        $io->open(array('path' => $this->_getFilePath()));
        // write file if not exists
        try {
            if (!file_exists($this->_getRealFilePath())) {
                if (!$io->write($this->_getRealFilePath(), $this->_getRealFilePath())) {
                    $this->_throwMageException('File cannot be written');
                }
            }
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }

        // is writable
        if ($io->fileExists($this->_getRealFilePath()) && !$io->isWriteable($this->_getRealFilePath())) {
            $this->_throwMageException('File is not writable');
        }

        $io->streamOpen($this->_getRealFilePath());

        // xml head
        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">');

        //xml content
        $sitemap = $this->_loadSitemap($sitemapData);
        $urls = $this->_getSiteUrls($sitemap);
        foreach ($urls as $url) {
            $images = $this->_getImagesDataFromUrl($url);
            if (empty($images)) {
                continue;
            }
            $singleNode = $this->_buildSingleNode($url, $images);
            $io->streamWrite($singleNode);
        }

        // xml bottom
        $io->streamWrite('</urlset>');

        $io->streamClose();

    }

    /**
     * Retrvieve images data (src and alt) from given url
     * @param string $url
     * @return array
     */
    protected function _getImagesDataFromUrl($url)
    {
        $out = array();
        $html = $this->_getFileContent($url);
        if ($html) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML($html);
            $dom->preserveWhiteSpace = false;
            $images = $dom->getElementsByTagName('img');
            foreach ($images as $key => $image) {
                if (!$this->_isset($image->getAttribute('src')) || !$this->_displayImage($image->getAttribute('src'))) {
                    continue;
                }
                $out[$key]['url'] = $image->getAttribute('src');
                if ($this->_isset($image->getAttribute('alt'))) {
                    $out[$key]['title'] = $image->getAttribute('alt');
                }
            }
        }
        return $out;
    }

    /**
     * Get file content from given url
     * @param string $url
     * @return mixed
     */
    protected function _getFileContent($url)
    {
        $request = curl_init();
        curl_setopt_array($request, array
        (
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER => FALSE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_CAINFO => 'cacert.pem',
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS => 10,
        ));
        $response = curl_exec($request);
        curl_close($request);
        return $response;
    }

    /**
     * Check image extension is allowed and image has local domain
     * @param $src
     * @return bool
     */
    protected function _displayImage($src)
    {
        $ext = pathinfo($src, PATHINFO_EXTENSION);
        return ($this->_enabledSrcDomain($src) && in_array($ext, $this->getEnabledExtensions()));
    }

    /**
     * Check if given src is under store site domain
     * @param string $src
     * @return bool
     */
    protected function _enabledSrcDomain($src)
    {
        $baseUrl = Mage::app()->getStore($this->_storeId)->getBaseUrl();
        return (strpos($src, $baseUrl) !== false);
    }

    /**
     * Load sitemap file
     * @param $sitemapData
     * @return SimpleXMLElement
     */
    protected function _loadSitemap($sitemapData)
    {
        $baseUrl = $this->_filePath = str_replace('//', '/', Mage::getBaseDir());
        $sitemapPath = $baseUrl . DS . $sitemapData['file'];
        $sitemap = simplexml_load_file($sitemapPath);
        return $sitemap;
    }

    protected function _isset($val) {
        return (isset($val) && $val != '');
    }

    /**
     * Build single xml node. Contains site url and images
     * @param string $url
     * @param array $images
     * @param string $priority
     * @return mixed
     */
    protected function _buildSingleNode($url, $images, $priority = '1.0') {
        $xml = '';
        if (!empty($images)) {
            $imgNode = '';
            foreach ($images as $img) {
                if (!isset($img['url'])) {
                    continue;
                }
                $imgNode .= '<image:image>';
                $imgNode .= '<image:loc>' . $this->onlydevReplace($img['url']) . '</image:loc>';
                if (isset($img['title'])) {
                    $imgNode .= '<image:title>' . $img['title'] . '</image:title>';
                }
                $imgNode .= '</image:image>';
            }
            $xml = sprintf(
                '<url><loc>%s</loc><priority>%s</priority>%s</url>'. "\n",
                htmlspecialchars($url),
                $priority,
                $imgNode
            );
        }
        return preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $xml);
    }

    protected function _throwMageException($msg)
    {
        Mage::throwException(__($msg));
    }

    public function getEnabledExtensions()
    {
        return $this->_enabledExtensions;
    }
}
