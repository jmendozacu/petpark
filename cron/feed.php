<?php
    if (isset($_GET['csv'])){
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="feed.csv"');
    }else{
      header('Content-Type: text/plain; charset=utf-8');
    }
    
    
    ini_set('memory_limit','512M');
    chdir(dirname(__FILE__).'/..');
    
    setlocale(LC_ALL, 'cs_CZ.UTF-8');
    include_once 'app/Mage.php';
    Mage::app();
    /*umask( 0 );
    Mage :: app()-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
    $userModel = Mage::getModel('admin/user');
    $userModel->setUserId(0);
    Mage::getSingleton('admin/session')->setUser($userModel);
    */
    function getArgs(){
        
        if (PHP_SAPI=='cli') { 
        //if (isset($_SERVER["SHELL"]) &&  $_SERVER["SHELL"] == '/bin/bash'){ //COMMAND LINE
            $args = array();
            $argv = $_SERVER['argv'];
            unset($argv[0]);
            foreach($argv as $arg){
              $parts = explode('=',$arg);
              $args[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
            }
            return $args;
        }else{ //HTTP
            return $_GET;
        }
    }
    
    /*$str = 'To je "fajne"'; 
    echo '"'.str_replace('"','""', $str).'"';
    exit;*/
    function f($str){
      return str_replace('"','""', $str); 
    }
    
    $file = 'export/feed.csv';
    $csv = new Zebu_Tools_CSV_AssocWriter($file, null, ',');
    $args = getArgs();
   // echo '"ID","Item title"'.PHP_EOL;
    $col = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('status',1)
            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
            //->addAttributeToFilter('type_id','simple');
    foreach($col as $product){
      //echo '"'.f($product->getSku()).'","'.f($product->getName()).'"'.PHP_EOL;
      $product->load($product->getId());
      $priceInclTax = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true,null, null, null, null, null, true);
      $csv->saveRow(
        array(
          'ID' => ($product->getSku()), 
          'Item title' => ($product->getName()),
          'Final URL' => Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).$product->getUrlPath(),
          'Image URL' => Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'media/catalog/product'.$product->getImage(), 
          //'Price' => $product->getFinalPrice().' '.	Mage::app()->getStore()->getCurrentCurrencyCode()
            'Price' => $priceInclTax.' '.	Mage::app()->getStore()->getCurrentCurrencyCode()

        )
      );
      //break;
    }
    $csv->close();
    readfile($file);