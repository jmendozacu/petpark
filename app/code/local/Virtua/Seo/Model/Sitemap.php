<?php

class Virtua_Seo_Model_Sitemap extends Mage_Core_Model_Abstract
{
    public function getCmsPageCollection()
    {
        $output = array();
        $cms = Mage::getModel('cms/page');
        $collection = $cms->getCollection();
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('title')
            ->columns('page_id');

        foreach ($collection->getData() as $value) {
            $url = Mage::helper('cms/page')->getPageUrl($value['page_id']);
            $output[$url] = $value['title'];
        }
        return $output;
    }

    public function getCategoryCollection()
    {
        $category = Mage::getModel('catalog/category')->getCollection();
        return $category;
    }

    public function getProductCollection()
    {
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('is_salable', '1')
            ->addFieldToFilter('is_in_stock', '1')
            ->addFieldToFilter(array(array('attribute'=>'visibility', 'neq'=>"1" )))
            ->addUrlRewrite();
        return $products;
    }

    public function getBlogCategoryCollection()
    {
        $output = array();
        $categories = Mage::getModel('blog/cat')
            ->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId());
        foreach ($categories->getData() as $cat) {
            $output[$cat['identifier']] = $cat['title'];
        }
        return $output;
    }

    public function getBlogPostCollection()
    {
        $output = array();
        $storeCode = Mage::app()->getStore()->getId();
        $blogApi = Mage::getModel('blog/api');
        // enabled
        $status = array(1);
        // 1 => sk , 2 => cz
        $store = array($storeCode);
        $posts = $blogApi->getPosts($status, $store);
        foreach ($posts->getData() as $post) {
            $output[$post['identifier']] = $post['title'];
        }
        return $output;
    }
}