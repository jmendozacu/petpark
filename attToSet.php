<?php

ini_set('memory_limit', '32M');
set_time_limit (0);
require_once'app/Mage.php';
Mage::app();
/*
//Zebu_HeurekaReviews::saveReview(array('sku'=>'test','rating_id'=>10,'summary'=>'fajne'));
Zebu_HeurekaReviews::saveReview(array('sku'=>'test2','rating_id'=>12,'summary'=>'toto je dvojka'));
$reviews = Zebu_HeurekaReviews::getProductReviews('test');
Zend_Debug::dump($reviews);
*/
// $o = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('o',42));
// Mage::helper('transportservice')->export($o);

// exit;


function createAttribute1($section)
{
    $attribute = Mage::getModel('catalog/resource_eav_attribute');
    $_attribute_data = array(
        'attribute_code' => 'qty_'.$section,
        'is_global' => '1',
        'frontend_input' => 'text', //'boolean',
        'default_value_text' => '',
        'default_value_yesno' => '0',
        'default_value_date' => '',
        'default_value_textarea' => '',
        'is_unique' => '0',
        'is_required' => '0',
        'apply_to' => '0', //array('grouped')
        'is_configurable' => '0',
        'is_searchable' => '0',
        'is_visible_in_advanced_search' => '0',
        'is_comparable' => '0',
        'is_used_for_price_rules' => '0',
        'is_wysiwyg_enabled' => '0',
        'is_html_allowed_on_front' => '1',
        'is_visible_on_front' => '0',
        'used_in_product_listing' => '0',
        'used_for_sort_by' => '0',
        'frontend_label' => 'Množství '.$section,

    );

    $attribute->addData($_attribute_data);
    //$attribute->setAttributeSetId(4);
    //$attribute->setAttributeGroupId(7);
    $attribute->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
    $attribute->setIsUserDefined(1);


    try {
        $attribute->save();
        $id = $attribute->getId();
        return $id;
        
    } catch (Exception $e) { echo '<p>Sorry, error occured while trying to save the attribute. Error: '.$e->getMessage().'</p>'; }  
}

$attributeId = Mage::app()->getRequest()->getParam('id');
if (!$attributeId) exit;
$attributeIds = [$attributeId];



$setup = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup();                

$entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
$setup = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup();
$model  = Mage::getModel('eav/entity_attribute_set')->setEntityTypeId($entityTypeId);
//die($model->getCollection()->getSelect());
/*$codes = Mage::helper('transportservice')->getCitiesCodes('Praha, Kralupy nad Vltavou, Kolín, České Budějovice, Benešov, Cheb, Plzeň, Beroun, Lovosice, Liberec, Ústí nad Labem, Mladá Boleslav, Pardubice, Hradec Králové, Trutnov, Brno, Uherské Hradiště, Hodonín, Třebíč, Svitavy, Jihlava, Olomouc, Šumperk, Nový Jičín, Ostrava');
Zend_Debug::dump($codes);

exit;
*/
//$attId = 484;

foreach($model->getCollection()->addFieldToFilter('entity_type_id',$entityTypeId)/*->addFieldToSelect('attribute_set_name')*/ as $set){
  //$groupId = $setup->getAttributeGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), 'General', 'attribute_group_id');
  $groupId = $setup->getAttributeGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), 'Prices', 'attribute_group_id'); 

  echo $set->getAttributeSetName().' / '. $setup->getAttributeGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), 'Prices', 'attribute_group_name').'<br />';
  $i = 0;
  foreach($attributeIds as $attId){
    $setup->addAttributeToGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), $groupId, $attId, 1);
  }
  echo '<hr />';
  // $setup->addAttributeToGroup(Mage_Catalog_Model_Product::ENTITY, $set->getId(), $groupId, $attId2, 11/*2*/);
  //exit;
  //$set->delete();
}

