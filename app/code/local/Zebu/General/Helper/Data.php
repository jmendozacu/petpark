<?php
class Zebu_General_Helper_Data extends Mage_Core_Helper_Abstract
{
  public function getBataPrice($price){
    $convertedPrice = Mage::helper('directory')->currencyConvert($price,'EUR','CZK');
    //return $convertedPrice;
    
    $convertedPrice *= 1.2;
    if ($convertedPrice>10){
      $convertedPrice = 10 * floor($convertedPrice/10) - 1;
    }
    $convertedPrice /= 1.2;
    return $convertedPrice; 
  }
  
  public function updateCzPrices($productId){
    $model = Mage::getModel('catalog/product');
    $conn = $model->getCollection()->getConnection();
    $resource = $model->getResource();       
            
    $price = $resource->getAttributeRawValue($productId, 'price', 0);
    $specialPrice = $resource->getAttributeRawValue($productId, 'special_price', 0);
    
       $action = Mage::getModel('catalog/resource_product_action');
       $data = array('price' => $this->getBataPrice($price));
       if ($specialPrice){
          $data['special_price'] = $this->getBataPrice($specialPrice);
       }
       $action->updateAttributes(array($productId), $data, 2);
    
    
    return; //zatim neresit group price
    
    
    $groupPriceTable = $resource->getTable('catalog/product_attribute_group_price');
    
    $query = 'SELECT * FROM ' . $groupPriceTable.' WHERE entity_id='.$productId;
    $gp = $conn->fetchAll($query);
    
    $groupPrice = array();
    $czPricesIds = array();
    $czPrices = array();
    foreach($gp as $i => $group){
        if ($group['website_id']==2){
          $czPricesIds[$group['customer_group_id']] = $group['value_id'];
          continue;
        }     
        $groupPrice[$i] = $group;
        unset($group['value_id']);
        $group['website_id'] = 2;
        $group['value'] = $this->getBataPrice($group['value']);
        $czPrices[$group['customer_group_id']] = $group;
    }
    
    foreach($czPricesIds as $cus => $id){
        $czPrices[$cus]['value_id'] = $id;
    }
    
    foreach($czPrices as $czGroup){
      $groupPrice[] = $czGroup;
    }
    
    foreach($groupPrice as $data){
      $conn->insertOnDuplicate($groupPriceTable, $data);
    }
  }
  
}
	 