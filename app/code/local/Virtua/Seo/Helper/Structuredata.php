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
}