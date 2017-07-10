<?php
    header('Content-Type: text/html; charset=utf-8');
    ini_set('memory_limit','512M');
    chdir(dirname(__FILE__).'/..');
    
    setlocale(LC_ALL, 'cs_CZ.UTF-8');
    include_once 'app/Mage.php';
    Mage::app();
    umask( 0 );
    Mage :: app()-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
    $userModel = Mage::getModel('admin/user');
    $userModel->setUserId(0);
    Mage::getSingleton('admin/session')->setUser($userModel);

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
    
    $args = getArgs();
  
    if(!isset($args['skip'])){
      try{
          /*
          Mage::getSingleton('catalog/url')->refreshRewrites();
          Mage :: getSingleton( 'catalog/index' ) -> rebuild();
          */
          
          $processes = Mage::getSingleton('index/indexer')->getProcessesCollection();
          $processes->walk('reindexAll');
          
          /*$indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection(); 
          foreach ($indexingProcesses as $process) {
                $process->reindexEverything();
          }*/
          
      }catch(Exception $e){
          echo 'Error: ' . $e->getMessage();    
      }
    }
    
    
        // google merchant
        /*Zebu_Export_ExportCreator::export('./export/fb.xml',
          'Zebu/Export/Transform_templates/google-cz.pxml',
          array('sku','name','ean','price','manufacturer','availability','description','url_path','image','category_ids'),
          'SHOP',
          array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                'status'=>1,
                'price'=>array('gt' => 0)),
          null,
          null,
          'rss2'
        );*/    
    
    
    

    /*if(isset($args['reindex'])){
        Mage::getSingleton('catalog/index')->rebuild();
        //Mage::getResourceModel('catalog/product_flat_indexer')->rebuild();
        Mage::getSingleton('catalog/url')->refreshRewrites();
        //Mage::getModel('catalog/product_image')->clearCache();
        Mage::getSingleton('catalogsearch/fulltext')->rebuildIndex();
        Mage::getSingleton('cataloginventory/stock_status')->rebuild();
        $flag = Mage::getModel('catalogindex/catalog_index_flag')->loadSelf();
        if ($flag->getState() == Mage_CatalogIndex_Model_Catalog_Index_Flag::STATE_RUNNING) {
            $kill = Mage::getModel('catalogindex/catalog_index_kill_flag')->loadSelf();
            $kill->setFlagData($flag->getFlagData())->save();
        }
        $flag->setState(Mage_CatalogIndex_Model_Catalog_Index_Flag::STATE_QUEUED)->save();
        Mage::getSingleton('catalogindex/indexer')->plainReindex();
    }*/

    
    //$storeCode = $_SERVER['SERVER_NAME']=='1fitness.cz' ? 'fitness' : 'sportpower';
    $stores = Mage::app()->getStores(false, true);

    Zend_Debug::dump(array_keys($stores));

    foreach($stores as $store){
      Mage::app()->setCurrentStore($store);
      $storeCode = $store->getCode(); 
      echo $storeCode.'<br/>';

        $file = $storeCode == 'default' ? './export/fb.xml' : './export/'.$storeCode.'_fb.xml'; 
        Zebu_Export_ExportCreator::export($file,
          'Zebu/Export/Transform_templates/google-cz.pxml',
          array('sku','name','ean','price','manufacturer','availability','description','url_path','image','category_ids'),
          'SHOP',
          array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                'status'=>1,
                'price'=>array('gt' => 0)),
          null,
          null,
          'rss2'
        ); 
    
    $export = './export/'.$storeCode.'_heureka-variants.xml';
    Zebu_Export_ExportCreator::export($export,
      'Zebu/Export/Transform_templates/heureka-variants.pxml',
      array('sku','ean','name','name_heureka','category_heureka','price','special_price','manufacturer','availability','description','url_path','image','category_ids','is_freeshipping'),
      'SHOP',
      array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            'status'=>1,
            //'price'=>array('gt' => 0)
            ),
      true/*,
      1,
      10*/)
    ;
    
    
    }
        

  
  
?>
