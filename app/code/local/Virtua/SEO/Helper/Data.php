<?php

class Virtua_SEO_Helper_Data extends Mage_Core_Helper_Abstract
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