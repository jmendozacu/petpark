<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '32M');
set_time_limit (0);
require_once'app/Mage.php';
Mage::app();

//Mage::helper('zebu')->updateCzPrices(1333);
//exit;

function changePrice($p){
  return $p / 1.2;
}

function getBataPrice($price){
  $convertedPrice = Mage::helper('directory')->currencyConvert($price,'EUR','CZK');
  echo '['.$convertedPrice.']'; 
  if ($convertedPrice>10){
    $convertedPrice = 10 * floor($convertedPrice/10) - 1;
  }
  return $convertedPrice; 
}

$productId = 1333;

//$model->getResource()
$model = Mage::getModel('catalog/product');
$conn = $model->getCollection()->getConnection();
$resource = $model->getResource();       
 

foreach(Mage::getModel('catalog/product')->getCollection()->getAllIds() as $productId){
    //if ($productId == 1333) continue;
    echo '<h1>'.$productId.'</h1>'; 
    
    continue;	

    /*if (!$calcDisabled){
      Mage::helper('zebu')->updateCzPrices($productId);
    }
    continue;    */
        
    $calcDisabled = $resource->getAttributeRawValue($productId, 'price_calculation_disabled', 0);
    $price = $resource->getAttributeRawValue($productId, 'price', 0);
    $specialPrice = $resource->getAttributeRawValue($productId, 'special_price', 0);
    var_dump($price);
    var_dump($specialPrice);

       $action = Mage::getModel('catalog/resource_product_action');
       $data = array('price' => changePrice($price));
       if ($specialPrice){
          $data['special_price'] = changePrice($specialPrice);
       }
       $action->updateAttributes(array($productId), $data, 0);

    if (!$calcDisabled){
      Mage::helper('zebu')->updateCzPrices($productId);
    }


    $groupPriceTable = $resource->getTable('catalog/product_attribute_group_price');

    $query = 'SELECT * FROM ' . $groupPriceTable.' WHERE entity_id='.$productId;
    $gp = $conn->fetchAll($query);
    var_dump($gp);
    echo '<hr />'.$groupPriceTable;

    $groupPrice = array();
    $czPricesIds = array();
    $czPrices = array();
    foreach($gp as $i => $group){
        /*if ($group['website_id']==2){
          $czPricesIds[$group['customer_group_id']] = $group['value_id'];
          continue;
        }*/     
        $group['value'] = changePrice($group['value']); 
        $groupPrice[$i] = $group;

    }

    var_dump($groupPrice);
    //exit;
    foreach($groupPrice as $data){
      $conn->insertOnDuplicate($groupPriceTable, $data);
    }

}

exit;

$groupPrice = array();
$czPricesIds = array();
$czPrices = array();
foreach($product->getData('group_price') as $i => $group){
    if ($group['website_id']==2){
      $czPricesIds[$group['cust_group']] = $group['price_id'];
      continue;
    }     
    $groupPrice[$i] = $group;
    unset($group['price_id']);
    $group['website_id'] = 2;
    $group['price'] = $group['website_price'] = 10*$group['price'];
    $czPrices[$group['cust_group']] = $group;
}

//var_dump($czPricesIds);
//var_dump($czPrices);

foreach($czPricesIds as $cus => $id){
    $czPrices[$cus]['price_id'] = $id;
}

foreach($czPrices as $czGroup){
  $groupPrice[] = $czGroup;
}

var_dump($groupPrice);
$product->setData('group_price', $groupPrice);

/*
$model = Mage::getModel('catalog/product');
        $conn = $model->getCollection()->getConnection();
        $groupPriceTable = $model->getResource()->getTable('catalog/product_attribute_group_price');


echo '<hr />'.$groupPriceTable;
var_dump($groupPrice);
*/
//$conn->insertOnDuplicate($groupPriceTable, $groupPrice);

//$product->save();

exit;


$product = Mage::getModel('catalog/product')->setStoreId(1)->load(1333);
var_dump($product->getGroupPrice());
var_dump($product->getData('group_price'));

$groupPrice = array();

foreach($product->getData('group_price') as $i => $group){
  /*
array (size=6)
      'price_id' => string '3230' (length=4)
      'website_id' => string '1' (length=1)
      'all_groups' => string '0' (length=1)
      'cust_group' => string '2' (length=1)
      'price' => float 7.93
      'website_price' => float 7.93  
  */
  unset($group['price_id']);
  $group['website_id'] = 2;
  $group['price'] = $group['website_price'] = 10*$group['price'];
  $groupPrice[$i] = $group; 
}
var_dump($groupPrice);
$product = Mage::getModel('catalog/product')->setStoreId(2)->load(1333);
var_dump($product->getGroupPrice());
var_dump($product->getData('group_price'));
//$product->setData('group_price', $groupPrice);
//$product->save();

exit;

$cat = Zebu_Mage_ProductHelper::get_category_max_pathes_array('2,3,4,5,6,7,113,114',' | ',0);

var_dump($cat);       


