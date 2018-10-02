<?php

$installer = $this;
$installer->startSetup();

$entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
$categoryTypeId = Mage::getModel('catalog/category')->getResource()->getTypeId();
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

if(!($installer->getAttributeId($entityTypeId, 'name_seo'))) {
    $installer->addAttribute('catalog_product', "name_seo", array(
        'type' => 'varchar',
        'input' => 'text',
        'label' => 'Name (SEO)',
        'sort_order' => 300,
        'required' => false,
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'user_defined' => true,
        'visible_on_front' => false
    ));
}

if(!($installer->getAttributeId($categoryTypeId, 'name_seo'))) {
    $installer->addAttribute('catalog_category', "name_seo", array(
        'group' => 'General Information',
        'type' => 'varchar',
        'input' => 'text',
        'label' => 'Name (SEO)',
        'sort_order' => 300,
        'required' => false,
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'user_defined' => true,
        'visible' => true,
        'visible_on_front' => false
    ));
}

$installer->endSetup();
