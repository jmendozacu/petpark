<?php

$installer = $this;
$installer->startSetup();

$productAttributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product','name_seo');
$productAttributeModel->setFrontendLabel('H1 name (SEO)')->save();
$categoryAttributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_category','name_seo');
$categoryAttributeModel->setFrontendLabel('H1 name (SEO)')->save();

$installer->endSetup();
