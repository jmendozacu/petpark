<?php

class Virtua_Seoattributes_Model_Observer
{
    /**
     * Remove all seo attributes connected to updated category
     * @param $observer
     */
    public function removeSeoattributesOnUpdateCategory($observer)
    {
        $event = $observer->getEvent();
        $category = $event->getCategory();
        if ($category->getId()) {
            $seoAttributesModel = Mage::getModel('virtua_seoattributes/seoattributes');
            $seoAttributesModel->getResource()->removeRowByCategoryId($category->getId());
        }
        return;
    }
}