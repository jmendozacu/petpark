<?php

ini_set('memory_limit', '32M');
set_time_limit (0);
require_once'app/Mage.php';
Mage::app();

$entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
$setup = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup();
$model  = Mage::getModel('eav/entity_attribute_set')->setEntityTypeId($entityTypeId);

$attributeIds = [206, 207];

foreach($model->getCollection()->addFieldToFilter('entity_type_id',$entityTypeId)/*->addFieldToSelect('attribute_set_name')*/ as $set){
  $groupId = $setup->getAttributeGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), 'General', 'attribute_group_id');
  /*if (!$groupId){
    $modelGroup = Mage::getModel('eav/entity_attribute_group');
    $modelGroup->setAttributeGroupName('Sklady');
    $modelGroup->setAttributeSetId($set->getId());
    $modelGroup->save();
    $groupId = $modelGroup->getId();
  }*/
  
  echo $set->getAttributeSetName().' / '. $setup->getAttributeGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), 'General', 'attribute_group_name').'<br />';
  $i = 0;
  foreach($attributeIds as $attId){
    echo "Add $attId <br />";
    $setup->addAttributeToGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), $groupId, $attId, (1000+$i++));
  }
  echo '<hr />';
  // $setup->addAttributeToGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), $groupId, $attId2, 11/*2*/);
  //exit;
  //$set->delete();
}

