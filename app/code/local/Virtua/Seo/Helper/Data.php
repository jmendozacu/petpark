<?php

class Virtua_Seo_Helper_Data extends Mage_Core_Helper_Abstract
{
    const BLOG_LIST_ROUTE = 'blog';
    const BLOG_LIST_ACTION = 'list';

    const REL_NEXT = 'next';
    const REL_PREV = 'prev';

    /**
     * Checks if user is currently on blog list page
     * @param string $route
     * @param string $action
     * @return bool
     */
    public function isBlogListPage($route, $action)
    {
        return ($route == self::BLOG_LIST_ROUTE && $action == self::BLOG_LIST_ACTION);
    }

    /**
     * Returns html link tag(s)
     * @param string $code
     * @param array $params
     * @param string $route
     * @return string
     */
    public function getRelLink($params, $route)
    {
        if (!$route) {
            return '';
        }
        try {
            $baseUrl = Mage::getBaseUrl() . $route;
            $currentPageNumber = (!empty($params) && isset($params['p'])) ? $params['p'] : '1';
            // first page - returns only next page tag
            if ($currentPageNumber == '1') {
                $currentPageNumber++;
                $href = $this->_getHrefParams($baseUrl, $currentPageNumber);
                return $this->_generateRelLink(self::REL_NEXT, $href);
            }
            $storeId = Mage::app()->getStore()->getStoreId();
            $model = Mage::getModel('virtua/seo');
            $postsCount = $model->getPostsCount($storeId);
            $postsPerPage = $model->getPostsPerPage($storeId);
            $maxPageNumber = ceil($postsCount / $postsPerPage);
            // last page - returns only previous page tag
            if ($currentPageNumber == $maxPageNumber) {
                $currentPageNumber--;
                $href = $this->_getHrefParams($baseUrl, $currentPageNumber);
                return $this->_generateRelLink(self::REL_PREV, $href);
            }
            // middle page - returns both: previous and next page tag
            $prevNumber = $currentPageNumber - 1;
            $prevHref = $this->_getHrefParams($baseUrl, $prevNumber);
            $nextNumber = $currentPageNumber + 1;
            $nextHref = $this->_getHrefParams($baseUrl, $nextNumber);
            return $this->_generateRelLink(self::REL_PREV, $prevHref) . $this->_generateRelLink(self::REL_NEXT, $nextHref);
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }
        return '';
    }

    /**
     * Returns alternate html tag (example: <link rel="alternate" hreflang="sk" href="http://petpark.sk" />)
     * @param $request
     * @return string
     */
    public function getAlternateTag($request)
    {
        if (!$request) {
            return '';
        }
        try {
            $alternateData = $this->_getAlternateData();
            $route = $request->getRouteName();
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            $path =  $this->_buildUrlPath($route, $controller, $action);
            $params = $request->getParams();
            $paramsString = $this->_buildParamsString($params);
            $alternateBaseUrl = Mage::app()->getStore($alternateData['alternateCode'])->getBaseUrl();
            $targetPath = $path . $paramsString;
            $targetPath = ltrim($targetPath, '/');
            $model = Mage::getModel('virtua/seo');
            $requestPath = $model->getRequestPathFromTargetPath($targetPath, $alternateData['alternateCode']);
            if ($requestPath) {
                $alternateUrl = $alternateBaseUrl . $requestPath;
            } else {
                $alternateUrl = $alternateBaseUrl . $targetPath;
            }
            return $this->_buildAlternateLinkTag($alternateData['alternateLang'], $alternateUrl);
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }
        return '';
    }

    /**
     * @param $alternateLang
     * @param $alternateUrl
     * @return string
     */
    protected function _buildAlternateLinkTag($alternateLang, $alternateUrl)
    {
        $alternateUrl = rtrim($alternateUrl, '/');
        $tag = '<link rel="alternate" hreflang="' . $alternateLang . '" href="' . $alternateUrl . '" />';
        return $tag;
    }

    /**
     * @param $params
     * @return string
     */
    protected function _buildParamsString($params)
    {
        $paramsString = '';
        if (!empty($params)) {
            $paramsString .= '/';
            foreach ($params as $key => $param) {
                if ($param) {
                    $paramsString .= $key . '/' . $param . '/';
                }
            }
            $paramsString = rtrim($paramsString, '/');
        }
        return $paramsString;
    }

    /**
     * @param $route
     * @param $controller
     * @param $action
     * @return string
     */
    protected function _buildUrlPath($route, $controller, $action)
    {
        $path = '';
        // home page
        if ($route == 'cms' && $controller == 'index' && $action == 'index') {
            return $path;
        }
        // for example blog page
        if ($controller == 'index' && $action == 'index') {
            $path .= DS . $route;
            return $path;
        }
        $path .= DS . $route . DS . $controller . DS . $action;
        return $path;
    }

    /**
     * @return array
     */
    protected function _getAlternateData()
    {
        if (Mage::app()->getStore()->getCode()=='cz') {
            $data = array(
                'alternateLang' => 'sk',
                'alternateCode' => 'default'
            );
        } else {
            $data = array(
                'alternateLang' => 'cz',
                'alternateCode' => 'cz'
            );
        }
        return $data;
    }

    /**
     * Returns url (example: 'http://domain.com?p=1')
     * @param string $baseUrl
     * @param int $pageNumber
     * @return string
     */
    protected function _getHrefParams($baseUrl, $pageNumber)
    {
        return $baseUrl . '?p=' . $pageNumber;
    }

    /**
     * Returns html tag link (example: <link rel="next" href="http://domain.com?p=1" />
     * @param string $rel (next / prev)
     * @param string $href
     * @return string
     */
    protected function _generateRelLink($rel, $href)
    {
        return '<link rel="' . $rel . '" href="' . $href .  '" />';
    }
}