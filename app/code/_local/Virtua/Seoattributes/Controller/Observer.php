<?php

class Virtua_Seoattributes_Controller_Observer
{
    public function saveAttributeObserver()
    {
        $attrId = Mage::app()->getRequest()->getParam('attribute_id');
        if (!$attrId) {
            return;
        }
        $attributeModel = Mage::getModel('eav/entity_attribute')->load($attrId);
        $helper = Mage::helper('virtua_seoattributes');
        if (!$attributeModel->getAttributeCode() || in_array($attributeModel->getAttributeCode(), $helper->getExcludedAttributes())) {
            return;
        }
        $seoAttributesModel = Mage::getModel('virtua_seoattributes/seoattributes');
        $seoAttributesModel->getResource()->removeRowByAttributeCode($attributeModel->getAttributeCode());
        return;
    }
}