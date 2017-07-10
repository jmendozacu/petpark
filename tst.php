<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '32M');

error_reporting(E_ALL);
ini_set('display_startup_errors',1);
ini_set('display_errors', 1);

set_time_limit (0);
require_once'app/Mage.php';
Mage::app();

//vypnu FLAT
$process = Mage::helper('catalog/product_flat')->getProcess();
$status = $process->getStatus();
$process->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
/** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
//$products  = Mage::getResourceModel('catalog/product_collection'); // Use EAV tables

//die('zlo');

$col = Mage::getModel('catalog/product')->getCollection();//->addAttributeToFilter('price_calculation_disabled',1);//->addAttributeToSelect(array('sku','name'));
//die('test1');
foreach($col as $p){
  echo '['.$p->getSku().$p->getName().']<br/>';
}

$process->setStatus($status);
echo ' KONEC ';
