<?php

class Virtua_SEO_Model_Seo extends Mage_Core_Model_Abstract
{
    /**
     * Returns number of enabled posts assigned to the store version (cz/sk)
     * @param $code
     * @return int
     */
    public function getPostsCount($code)
    {
        $blog = Mage::getModel('blog/api');
        // enabled
        $status = array(1);
        // 1 => sk , 2 => cz
        $store = array($code);
        $posts = $blog->getPosts($status, $store);
        return count($posts);
    }

    /**
     * Returns number of posts per page from settings
     * @param $store
     * @return mixed
     */
    public function getPostsPerPage($store)
    {
        $helper = Mage::helper('blog');
        return $helper->postsPerPage($store);
    }

}