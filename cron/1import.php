<?php
//header('Content-Type: text/html; charset=utf-8');

ini_set('display_errors',1);
error_reporting(E_ALL);

//ini_set('memory_limit','512M');
//ini_set('max_execution_time','18000');

chdir(dirname(__FILE__).'/..');

//setlocale(LC_COLLATE | LC_CTYPE, 'cs_CZ.UTF-8');
setlocale(LC_ALL, 'cs_CZ.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');

include_once 'app/Mage.php';
//Mage::app();
echo '<pre>';
ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);


    Mage :: app()-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
    $userModel = Mage::getModel('admin/user');
    $userModel->setUserId(0);
    Mage::getSingleton('admin/session')->setUser($userModel);

if (1 || isset($_GET['log'])){
  Zebu_Auxiliary::set_output_redirect('cron/import.log');
}

   /* $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
    $req = array(
               'shoe_size',
                    'price',
                    'model',
                    'description',
                    'in_depth',
                    'media_gallery',
                 'short_description');
                
    foreach($req as $a){
        $setup->updateAttribute('catalog_product', $a, 'is_required', '0');
    }
    
    exit;*/

    
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

//http://kvalitazamalo.sk/cron/import.php?download&parsemain&importmain&index&log
//Zebu_Auxiliary::info_variable($_SERVER);

//Zebu_Auxiliary::info_variable(PHP_SAPI);
//Zebu_Auxiliary::info_variable($_GET);
$_GET = getArgs();

$params = array();

foreach($_GET as $param => $nil){
   $params[preg_replace('~amp;~','', $param)] = 1;
}

$_GET = $params;
    
Zebu_Auxiliary::info_variable($_GET);

class Importer{
    
    public function parseMain($out){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://eshops.inteo.sk/api/v1/products/");
        //curl_setopt($ch, CURLOPT_URL, "https://private-anon-b1f0433f2-inteo.apiary-proxy.com/api/v1/products/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          //"Authorization: Bearer b8536c6e-b875-4d4c-8977-35730699e9e5"
          "Authorization: Bearer 91778630-e45b-4390-b5f2-477da9620d47"  
        ));                      

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);
        file_put_contents('response.json', $response);
        $responseArray = json_decode($response, true);        

        $i=0;
        
        $supportedTypes = array(
          'simple',
          'configurable',
          'virtual',
          'grouped'
        );
        
        $col = Mage::getModel('catalog/product')->getCollection()
        /*->addAttributeToFilter('type_id',array('in'=>array(
          'simple',
          'configurable',
          'virtual',
          'grouped'
        )))*/;
        
        
        $products = array();

        foreach($col as $p){
          $products[$p->getSku()] = $p->getTypeId();
        }

        
        $csv = new Zebu_Tools_CSV_AssocWriter($out,
                array(
                    'sku', 'ean','qty','in_in_stock'                 
                )
                ,',');

        $count = 0;
        $processedSkus = array(); 
        foreach($responseArray['products'] as $data){
             $rows = array();

             $sku = trim($data['stockCardNumber']);
             $ean = trim($data['ean']);
             $qty = (int)$data['count'];
             //echo "$sku: $id<br/>";

             if (!isset($products[$sku])){
                //echo "Neznam $sku, mozna je bundle product.<br />";
                continue;
             }
             if (!in_array($products[$sku], $supportedTypes)){
                echo "$sku je typu $products[$sku], nelze importovat.<br />";
                continue;
             }
             
             if (isset($processeSkus[$sku])){
                echo "Duplicitni SKU: $sku<br />";
                continue;
             }
             
             $processeSkus[$sku] = $sku;             
             
             $row = array(
               'sku' => $sku,
               'ean' => $ean,
               'qty' => $qty,
               'in_in_stock' => (int) ($qty > 0)
             );

             $csv->saveRow($row);
             $count++;
          }
          echo "Sparovano: $count<br />";
        
        //Zend_Debug::dump($parameters);
    }

    protected $typeChangeIds = array();
            
    public function update($out){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://eshops.inteo.sk/api/v1/products/");
        //curl_setopt($ch, CURLOPT_URL, "https://private-anon-b1f0433f2-inteo.apiary-proxy.com/api/v1/products/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          //"Authorization: Bearer b8536c6e-b875-4d4c-8977-35730699e9e5"
          "Authorization: Bearer 91778630-e45b-4390-b5f2-477da9620d47"  
        ));                      

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);

        $data = json_decode($response, true);       
       
        $i=0;
        //$col = Mage::getModel('catalog/product')->getCollection()/*->addAttributeToSelect(array('name','price'))*/;
        
        
        $con = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table = Mage::getSingleton('core/resource')->getTableName('catalog/product');
        $select = $con->select()->from($table, array('sku', 'entity_id'));
        $skuIds = $con->fetchPairs($select);        
        
        $skus = array();
        
        $rows = array();
        
        foreach($data as $p){
             $rows = array();

             $sku = $data['stockCardNumber'];
             $ean = $data['ean'];
             $qty = $data['count'];
             //echo "$sku: $id<br/>";

             if (!isset($products[$sku])){
                continue;
             }

             $rows[$sku] = array(
               'sku' => $sku,
               'ean' => $ean,
               'qty' => $qty,
               'in_in_stock' => (int) ($qty > 0)
             );
          } 
        
        //Zend_Debug::dump($parameters);

    $resource = Mage::getSingleton('core/resource');
    $writeConnection = $resource->getConnection('core_write');

    if (!empty($this->typeChangeIds)){
        Zend_Debug::dump($this->typeChangeIds);
        $table = $resource->getTableName('catalog/product');
        $ids = join(',', $this->typeChangeIds);
    
        $query = "UPDATE {$table} SET type_id = 'configurable' WHERE entity_id in ($ids)";
        echo $query;
        $writeConnection->query($query);
    }
    
    
    $col = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('external_id',array('notnull' => true))
    //->addAttributeToFilter('status', 1)
    ->addFieldToFilter('type_id', 'simple')
    ->addAttributeToFilter('sku', array('nin' => $skus));    
    $ids = $col->getAllIds();
    if (!empty($ids)){
        Zebu_Auxiliary::info_message("Varianty zmizely z feedu");
        Mage::getSingleton('catalog/product_action')->updateAttributes($ids, array('status'=>2),0);
        $ids = join(',', $ids);
        $query = "UPDATE cataloginventory_stock_item SET qty=0, is_in_stock =0 WHERE product_id in ($ids)";
        Zebu_Auxiliary::info_message($query);
        $writeConnection->query($query);
        $query = "UPDATE cataloginventory_stock_status SET qty=0, stock_status =0 WHERE product_id in ($ids)";
        $writeConnection->query($query);
        
        /*UPDATE cataloginventory_stock_item item_stock, cataloginventory_stock_status status_stock
SET item_stock.qty = 0, item_stock.is_in_stock = 0,
status_stock.qty = 0, status_stock.stock_status = 0
WHERE item_stock.product_id = '$product_id' AND item_stock.product_id = status_stock.product_id
*/        
  
    }
    
    //preulozeni
  /*  $col = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('external_id',array('notnull' => true))
    ->addFieldToFilter('type_id', 'configurable')
    //TypeId
    ->addAttributeToFilter('sku', array('in' => $skus));
        $ids = $col->getAllIds();
        Zend_Debug::dump($ids);
        
        
        
        $resource = Mage::getSingleton('core/resource');
    $writeConnection = $resource->getConnection('core_write');
    $table = $resource->getTableName('catalog/product');
    //$ids = join(',', $this->typeChangeIds);
        $ids = join(',', $ids);
        $query = "UPDATE {$table} SET has_options=1, required_options=1 WHERE entity_id in ($ids)";
        echo $query;
        $writeConnection->query($query);
        
     */   
        
        //Mage::getSingleton('catalog/product_action')->updateAttributes($ids, array('status'=>1),0);
        //Mage::getSingleton('catalog/product_action')->updateAttributes($ids, array('has_options'=>1),0);
        
/*        foreach($col as $p){
          $ids[] = array('id'=>$p->getId(),'type'=>$p->getTypeId(),'name' => $p->getName(), 'sku'=>$p->getSku(), 'price' => $p->getPrice());
        }*/
    
          
          
    }

  public function import($file) {
        Zebu_Auxiliary::info_message("Import $file...");
        if (!filesize($file)) {
            echo "Import file $file is empty \n";
            Mage::log("Import file $file is empty", Zend_Log::DEBUG);
        } else {

            $import = Mage::getModel('importexport/import');
            $import->setEntity('catalog_product');
            $import->setBehavior('append');

            $validationResult = $import->validateSource($file);
            $processedRowsCount = $import->getProcessedRowsCount();

            if ($processedRowsCount > 0) {

                // if type 'select' attribute options added, revalidate source (not necessary for categories)
                foreach ($import->getErrors() as $type => $lines) {
                    if (strpos($type, "added")) {
                        $import = Mage::getModel('importexport/import');
                        $import->setEntity('catalog_product');
                        $import->setBehavior('append');
                        $validationResult = $import->validateSource($file);
                        $processedRowsCount = $import->getProcessedRowsCount();

                        break;
                    }
                }

                if (!$validationResult) {

                    $message = sprintf("File %s contains %s corrupt records (from a total of %s)", $file, $import->getInvalidRowsCount(), $processedRowsCount
                    );
                    foreach ($import->getErrors() as $type => $lines) {
                        $message .= "\n:::: " . $type . " ::::\nIn Line(s) " . implode(", ", $lines) . "\n";
                    }

                    Zebu_Auxiliary::info_message("ERROR: $message", 2);
                    Mage::throwException($message);
                }

                $import->importSource();
            }
            echo "Done (processed rows count: " . $processedRowsCount . ")\n";
        }
        Zebu_Auxiliary::info_message("Import $file completed...");
    }
    
    public function reindex(){
      $indexes = array(
      'catalog_product_attribute',
      //'catalog_product_price',
      //'catalog_url',
      'catalog_product_flat',
      //'catalog_category_flat',
      //'catalog_category_product',
      'catalogsearch_fulltext',
      'cataloginventory_stock'
      //'tag_summary'
      );
      
      foreach($indexes as $index){
        $process = Mage::getModel('index/indexer')->getProcessByCode($index);
        Zebu_Auxiliary::info_message("$index reindex finished...");
        $process->reindexAll();
      }
    }
    
    
}

Zebu_Auxiliary::info_message("START");
$im = new Importer;
$file = 'cron/main.csv';

if (isset($_GET['parsemain'])){
  Zebu_Auxiliary::info_message("parsemain...");
  $im->parseMain($file);
  Zebu_Auxiliary::info_message("parsemain finished...");
}
if (isset($_GET['importmain'])){
  Zebu_Auxiliary::info_message("importmain...");
  $im->import($file);
  Zebu_Auxiliary::info_message("importmain finished...");
}

$file = 'cron/light.csv';
if (isset($_GET['parsevar'])){
  Zebu_Auxiliary::info_message("parsevar...");
  $im->parseVariants($file);
  Zebu_Auxiliary::info_message("parsevar finished...");
}
if (isset($_GET['importvar'])){
  Zebu_Auxiliary::info_message("importvar...");
  $im->import($file);
  Zebu_Auxiliary::info_message("importvar finished...");
}
if (isset($_GET['index'])){
  Zebu_Auxiliary::info_message("reindex...");
  $im->reindex();
  Zebu_Auxiliary::info_message("reindex finished...");
}
Zebu_Auxiliary::info_message("FINISHED");