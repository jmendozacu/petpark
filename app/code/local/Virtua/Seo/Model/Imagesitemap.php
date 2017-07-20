<?php

class Virtua_Seo_Model_Imagesitemap extends Mage_Core_Model_Abstract
{

    const MAP_EXTENSION = '.xml';
    const MAP_BASE_NAME = 'imagemap_';

    protected $_enabledExtensions = array(
        'jpg', 'jpeg',
    );

    protected $_filePath;

    /**
     * Return real file path
     *
     * @return string
     */
    protected function _getFilePath()
    {
        if (is_null($this->_filePath)) {
            $this->_filePath = str_replace('//', '/', Mage::getBaseDir()) . DS . 'var';
        }
        return $this->_filePath;
    }

    protected function _getFileName()
    {
        $fileName = self::MAP_BASE_NAME . strtolower(Mage::app()->getStore()->getName()) . self::MAP_EXTENSION;
        return $fileName;
    }

    protected function _getRealFilePath()
    {
        $io = new Varien_Io_File();
        return $io->getCleanPath($this->_getFilePath() . DS . $this->_getFileName());
    }

    protected function _getSiteUrls($sitemap)
    {
        $array = array();
        if ($sitemap) {
            foreach ($sitemap as $item) {
                if (!$item->loc) {
                    continue;
                }
                $loc = (array) $item->loc;
                $array[] = $this->_buildLocalUrl($loc[0]);
                //$array[] = $loc[0];
            }
        }
        return $array;
    }

    protected function _buildLocalUrl($url)
    {
        $urlArr = parse_url($url);
        if (!empty($urlArr)) {
            $baseUrl = Mage::getBaseUrl();
            if ($this->_isset($urlArr['path'])) {
                $url = rtrim($baseUrl, DS) . $urlArr['path'];
                $url = rtrim($url, DS);
//                if ($this->_isset($urlArr['query'])) {
//                    $url .= '&' . $urlArr['query'];
//                }
            }
        }
        return $url;
    }

    public function generateImageSitemap()
    {
        $io = new Varien_Io_File();

        $io->open(array('path' => $this->_getFilePath()));
        // write file if not exists
        if (!file_exists($this->_getRealFilePath())) {
            if (!$io->write($this->_getRealFilePath(), $this->_getRealFilePath())) {
                $this->_throwMageException('File cannot be written');
            }
        }
        // is writable
        if ($io->fileExists($this->_getRealFilePath()) && !$io->isWriteable($this->_getRealFilePath())) {
            $this->_throwMageException('File is not writable');
        }

        $io->streamOpen($this->_getFileName());

        // xml head
        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">');

        //xml content
        $sitemap = $this->_loadSitemap();
        $urls = $this->_getSiteUrls($sitemap);
        $i = 0;
        foreach ($urls as $url) {
            $i++;
            $images = $this->_getImagesFromUrl($url);
            if (empty($images)) {
                continue;
            }
            $singleNode = $this->_buildSingleNode($url, $images);
            $io->streamWrite($singleNode);
            if ($i > 10) {
                break;
            }
        }

        // xml bottom
        $io->streamWrite('</urlset>');

        $io->streamClose();

    }

    protected function _getImagesFromUrl($url)
    {
        $out = array();
        $html = $this->_getFileContent($url);
        if ($html) {
            $dom = new domDocument;
            $dom->loadHTML($html);
            $dom->preserveWhiteSpace = false;
            $images = $dom->getElementsByTagName('img');
            foreach ($images as $key => $image) {
                if (!$this->_isset($image->getAttribute('src')) || !$this->_displayImage($image->getAttribute('src'))) {
                    continue;
                }
                $out[$key]['src'] = $image->getAttribute('src');
                if ($this->_isset($image->getAttribute('alt'))) {
                    $out[$key]['title'] = $image->getAttribute('alt');
                }
            }
        }
        return $out;
    }

    protected function _displayImage($src)
    {
        $ext = pathinfo($src, PATHINFO_EXTENSION);
        return ($this->_enabledSrcDomain($src) && in_array($ext, $this->getEnabledExtensions()));
    }

    protected function _enabledSrcDomain($src)
    {
        $baseUrl = Mage::getBaseUrl();
        return (strpos($src, $baseUrl) !== false);
    }

    protected function _getFileContent($file)
    {
        $request = curl_init();
        curl_setopt_array($request, array
        (
            CURLOPT_URL => $file,
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

    protected function _loadSitemap()
    {
        $baseUrl = $this->_filePath = str_replace('//', '/', Mage::getBaseDir());
        $sitemapPath = $baseUrl . DS . 'sitemap.xml';
        //$sitemapString = $this->_getFileContent($sitemap);
        $sitemap = simplexml_load_file($sitemapPath);
        return $sitemap;
    }

    protected function _isset($val) {
        return (isset($val) && $val != '');
    }

    protected function _buildSingleNode($url, $images, $priority = '1.0') {
        $xml = '';
        if (!empty($images)) {
            $imgNode = '';
            foreach ($images as $img) {
                if (!$this->_isset($img['url'])) {
                    continue;
                }
                $imgNode = '<image:image>';
                $imgNode .= '<image:loc>' . $img['url'] . '</image:loc>';
                if ($this->_isset($img['title'])) {
                    $imgNode .= '<image:title>' . $img['title'] . '</image:title>';
                }
                $imgNode .= '</image:image>';
            }
            $xml = sprintf(
                '<url><loc>%s</loc><priority>%s</priority>%s</url>',
                htmlspecialchars($url),
                $priority,
                $imgNode
            );
        }
        return $xml;
    }

    protected function _throwMageException($msg)
    {
        Mage::log($msg);
        //Mage::throwException(__($msg));
        return $this;
    }

    public function getEnabledExtensions()
    {
        return $this->_enabledExtensions;
    }
}
