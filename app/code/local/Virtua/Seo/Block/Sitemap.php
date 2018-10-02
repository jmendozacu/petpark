<?php

class Virtua_Seo_Block_Sitemap extends Mage_Core_Block_Template
{
    protected $sitemapModel;

    public function __construct(array $args = array())
    {
        $this->sitemapModel = Mage::getModel('virtua/sitemap');
        parent::__construct($args);
    }

    public function getCmsPageCollection()
    {
        $cmsPageCollection = $this->sitemapModel->getCmsPageCollection();
        return $cmsPageCollection;
    }

    public function getBlogPostCollection()
    {
        $blogPostCollection = $this->sitemapModel->getBlogPostCollection();
        return $blogPostCollection;
    }

    public function getBlogCategoryCollection()
    {
        $blogCategoryCollection = $this->sitemapModel->getBlogCategoryCollection();
        return $blogCategoryCollection;
    }

    public function getCategoryCollection()
    {
        $categoryCollection = $this->sitemapModel->getCategoryCollection();
        return $categoryCollection;
    }

    /**
     * Returns url to blog post/category page
     * @param $identifier
     * @param string $route
     * @return string
     */
    public function buildBlogUrl($identifier, $route = 'blog')
    {
        $baseUrl = Mage::app()->getStore()->getBaseUrl();
        $url = $baseUrl . $route . DS . $identifier;
        return $url;
    }

    public function loadCategory($id)
    {
        $category = Mage::getModel('catalog/category')->load($id);
        return $category;
    }

    public function loadProduct($id)
    {
        $product = Mage::getModel('catalog/product')->load($id);
        return $product;
    }

    public function getProductCollection()
    {
        $productCollection = $this->sitemapModel->getProductCollection();
        return $productCollection;
    }
}
