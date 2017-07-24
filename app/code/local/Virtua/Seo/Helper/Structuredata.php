<?php

class Virtua_Seo_Helper_Structuredata extends Mage_Core_Helper_Abstract
{
    public function getJsonCompanyStructuredData()
    {
        $model = Mage::getModel('virtua/structuredata');
        return json_encode($model->getCompanyStructuredData());
    }

    public function getJsonPostData($postId)
    {
        $model = Mage::getModel('virtua/structuredata');
        $post = $model->getPostData($postId);
        return json_encode($post);
    }

    public function buildBreadcrumbsJson($data)
    {
        if (empty($data)) {
            return '';
        }
        $output = array(
            "@context" => "http://schema.org",
            "@type" => 'BreadcrumbList',
            "itemListElement" => array()
        );
        $position = 1;
        foreach ($data as $item) {
            $link = ($item['link']) ? $item['link'] : Mage::helper('core/url')->getCurrentUrl();
            $output["itemListElement"][] = array(
                "@type" => 'listItem',
                "position" => $position,
                "item" => array(
                    "@id" => $link,
                    "name" => $item['label']
                )
            );
            $position++;
        }
        //echo '<pre>'; print_r($output); die();
        return json_encode($output);
    }
}