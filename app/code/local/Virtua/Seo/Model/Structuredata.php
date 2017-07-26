<?php

class Virtua_Seo_Model_Structuredata extends Mage_Core_Model_Abstract
{
    const COMPANY_NAME = 'Pet Park';

    public function getCompanyStructuredData()
    {
        $storeId = Mage::app()->getStore()->getStoreId();
        $baseUrl = Mage::getBaseUrl();
        $output = array(
            '@context' => 'http://schema.org',
            '@type' => 'Organization',
            'url' => $baseUrl,
            'name' => self::COMPANY_NAME,
            'contactPoint' => array (
                '@type' => 'ContactPoint',
                'telephone' => Mage::getStoreConfig('general/store_information/phone', $storeId),
                'contactType' => 'customer support',
            ),
        );
        $menuHelper = Mage::helper('megamenu');
        if ($menuHelper->getLogoSrc()) {
            $output['logo'] = $menuHelper->getLogoSrc();
        }
        return $output;
    }

    public function getPostData($postId)
    {
        $post = Mage::getModel('blog/post')->load($postId);
        $baseMediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $menuHelper = Mage::helper('megamenu');
        $output = array(
            '@context' => 'http://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => array (
                '@type' => 'WebPage',
                '@id' => $this->_buildBlogUrl($post->getIdentifier())
            ),
            'headline' => $post->getTitle(),
            'image' => array(
                '@type' => 'ImageObject',
                'url' => $baseMediaUrl . $post->getImage(),
                'height' => 462,
                'width' => 700
            ),
            'datePublished' => $post->getCreatedTime(),
            'dateModified' => $post->getUpdateTime(),
            'author' => array(
                '@type' => 'Person',
                'name' => $post->getUpdateUser()
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => self::COMPANY_NAME,
                'logo' => array(
                    'type' => 'ImageObject',
                    'url' => $menuHelper->getLogoSrc(),
                    'height' => 51,
                    'width' => 197
                )
            ),
            'description' => $post->getMetaDescription()
        );
        return $output;
    }

    protected function _buildBlogUrl($identifier, $route = 'blog')
    {
        $baseUrl = Mage::app()->getStore()->getBaseUrl();
        $url = $baseUrl . $route . DS . $identifier;
        return $url;
    }
}
