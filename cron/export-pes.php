<?php
	chdir(dirname(__FILE__).'/..');
    require_once 'app/Mage.php';
    umask( 0 );
    Mage::app(); 

$processed = [];

header('Content-Type: text/xml');
$xml = '<?xml version="1.0"?>';
$xml .=  '<ESHOP>';    


if (isset($_GET['c'])){    
  //$customer = Mage::getModel('customer/customer')->load(2305);
  //$customer = Mage::getModel('customer/customer')->load(2237);
  $customer = Mage::getModel('customer/customer')->load($_GET['c']);
  //var_export($customer->getData());
  //$customer->setGroupId(5);
  $session = Mage::getSingleton( 'customer/session' );
  $session->setCustomerAsLoggedIn(  $customer );
}

    //$_product = $product = Mage::getModel('catalog/product')->load(1333);


$_coreHelper = Mage::helper('core');
$_weeeHelper = Mage::helper('weee');
$_taxHelper = Mage::helper('tax');


foreach(Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('status',1)->addAttributeToFilter('visibility', 4) as $product){
//foreach(array(1) as $i){     $_product = $product = Mage::getModel('catalog/product')->load(1333);
     $product = Mage::getModel('catalog/product')->load($product->getId());
     
    if (isset($processed[$product->getSku()])){
      Mage::log($product->getSku().' already processed.', null, 'export-pes.log');
      continue;
    }
    $processed[$product->getSku()] = 1;
     
     if($product->getTypeId() == "simple"):
           $xml .= '
    <PRODUCT>
      <SKU>'.$product->getSku().'</SKU>
      <EAN>'.$product->getEan().'</EAN>
      <PRICE>'.$_taxHelper->getPrice($product, $product->getFinalPrice(), false).'</PRICE>
      <AVAILABILITY>'.$product->getAttributeText('availability').'</AVAILABILITY>
    </PRODUCT>';
    endif;
     
    if($product->getTypeId() == "configurable"):
        
    $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
    //array to keep the price differences for each attribute value
    $pricesByAttributeValues = array();
    //base price of the configurable product 
    $basePrice = $product->getFinalPrice();
    //loop through the attributes and get the price adjustments specified in the configurable product admin page
    foreach ($attributes as $attribute){
        $prices = $attribute->getPrices();
        foreach ($prices as $price){
            if ($price['is_percent']){ //if the price is specified in percents
                $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
            }
            else { //if the price is absolute value
                $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
            }
        }
    }
        
    //get all simple products
    //$simple = $product->getTypeInstance()->getUsedProducts();
    $simple = $product->getTypeInstance()->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
    //loop through the products
    foreach ($simple as $sProduct){

        if (isset($processed[$sProduct->getSku()])){
          Mage::log($sProduct->getSku().' already processed [CONF]', null, 'export-pes.log');
          continue;
        }
        $processed[$sProduct->getSku] = 1;
    
        $totalPrice = $basePrice;
        //loop through the configurable attributes
        foreach ($attributes as $attribute){
            //get the value for a specific attribute for a simple product
            $value = $sProduct->getData($attribute->getProductAttribute()->getAttributeCode());
            //add the price adjustment to the total price of the simple product
            if (isset($pricesByAttributeValues[$value])){
                $totalPrice += $pricesByAttributeValues[$value];
            }
        }
        //in $totalPrice you should have now the price of the simple product
        //do what you want/need with it
        $xml .= '
        <PRODUCT type="conf">
          <SKU>'.$sProduct->getSku().'</SKU>
          <EAN>'.$sProduct->getEan().'</EAN>          
          <PRICE>'.$_taxHelper->getPrice($product, $totalPrice, false).'</PRICE>
          <AVAILABILITY>'.$sProduct->getAttributeText('availability').'</AVAILABILITY>
        </PRODUCT>';
        }    
    
    /*$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
    
    $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
    foreach($simple_collection as $simple_product){
      //echo $simple_product->getSku() . " - " . $simple_product->getName() . " - " . Mage::helper('core')->currency($simple_product->getFinalPrice()) . "<br>";
      $xml .= '
    <PRODUCT0>
      <SKU>'.$simple_product->getSku().'</SKU>
      <EAN>'.$simple_product->getEan().'</EAN>
      <PRICE>'.$_taxHelper->getPrice($simple_product, $simple_product->getFinalPrice()).'</PRICE>
      <AVAILABILITY>'.$simple_product->getAttributeText('availability').'</AVAILABILITY>
    </PRODUCT0>';
      //'$_taxHelper->getPrice($simple_product, $simple_product->getFinalPrice()).'<hr />';
    }*/
    endif;
    
      if ($product->getTypeId() == 'grouped'){
          // how do I now get associated products of $product?
          $simple = $product->getTypeInstance(true)->getAssociatedProducts($product);//->addAttributeToSelect('*');
          //$simple = $product->getTypeInstance()->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
          //loop through the products
          foreach ($simple as $sProduct){
              if (isset($processed[$sProduct->getSku()])){
                Mage::log($sProduct->getSku().' already processed [GROUPED]', null, 'export-pes.log');
                continue;
              }
              $processed[$sProduct->getSku] = 1;
                  
              $xml .= '
              <PRODUCT type="grouped">
                <SKU>'.$sProduct->getSku().'</SKU>
                <EAN>'.Mage::getResourceModel('catalog/product')->getAttributeRawValue($sProduct->getId(), 'ean', 0).'</EAN>
                <PRICE>'.$_taxHelper->getPrice($sProduct, $sProduct->getFinalPrice(), false).'</PRICE>
                <AVAILABILITY>'.$sProduct->getAttributeText('availability').'</AVAILABILITY>
              </PRODUCT>';
          }           
      }
    
    }
    
    $xml .= '</ESHOP>';    
    /*$p = Mage::getModel('catalog/product')->load(561); 
    echo '#'.$product->getTypeInstance(true)->getFinalPrice(1, $p);*/
    
    file_put_contents('export/spokojeny-pes.xml', $xml);
    
    echo $xml;
    
    exit;
        
    
    //;// "default" )
$stores = Mage::app()->getStores(false, true);
    //nastaveni SK
    //Mage::app()->setCurrentStore($stores['default']);
foreach(array('default' => 'default.csv','cz'=>'cz.csv') as $code => $exportFilename){
    //Mage::app($code);//
    Mage::app()->setCurrentStore($stores[$code]);





  $urlBase = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
  
  $groupId = Mage::app()->getRequest()->getParam('group',1);
  //echo $groupId; 
  //exit;
  $websiteId = 1;
  
  	$connection = Mage::getSingleton('core/resource')->getConnection('core_read');

		$select = $connection
                    ->select()
                    //->from('catalog_product_index_price');
                    ->from('catalog_product_index_price')
                    ->where('customer_group_id = '.(int) $groupId)
                    ->where('website_id = '.(int) $websiteId);
		$prices = array();
		foreach($connection->query($select) as $priceRow){
			$prices[$priceRow['entity_id']] = $priceRow['final_price'];			
		}
  
   
  //exit;
/*  
- kód zboží (jedinečný kód pro každé kolo)
- EAN (je li tato informace známa)
- Wheel CODE
- Wheel CODE R
- obrázek bez vodoznaku (klidně i více obrázků k jednomu kolu)
- značka (WSP atd.)
- model kola (Zurigo, Vancouver atd.)
- pro jakou značku vozů (Audi, BMW atd.)
- šířka
- průměr
- et
- rozteč
- barva
- středová díra (je li tato informace známa)
- hmotnost (je li tato informace známa)
- MOC bez DPH
- VOC bez DPH (naše nákupní cena)
- skladová dostupnost v kusech ČR
- skladová dostupnost v kusech výrobce
- poznámka ke skladové dostupnosti (např. do 10 dnů, u dodavatele apod.)
*/
  
    $fields = array(
    'sku'=>0,
    'bar_code' =>0,
    'name'=>0,
    'model'=>0,
    'manufacturer'=>0,
    //'availability',
    //'description',
    //'url_path',
    'image'=>0,
    'car_brand'=>0,
    'category_ids'=>0,
    'color'=>1,
    'wheel_code'=>0,
    'wheel_code_r'=>0,
    'pcd'=>1,
    'hub'=>1,
    'et'=>1,
    'disk_width'=>1,
    'disk_size'=>1,
    //'size'=>1,
    'is_in_stock'=>0,
    'availability_date'=>0,    
    'qty'=>0,
    'price'=>0,/*'manufacturer',*/
    'your price' => 0,
    );
  
  $filters = array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
          'status'=>1
          /*'price'=>array('gt' => 0)*/);

  //$exportFilename = 'data.csv';
  $exportFilepath = 'export/private/'.$exportFilename;
  //$exportName = 'cenik_'.date('Y-m-d_H-i').'.csv';
  
  $cols = array_diff(  
      array_keys($fields),
      array('name', 'category_ids')
  );
  
  //$cols[] = 'your price';
  
  //$exportCsv = new Skvely_Tools_CSV_AssocWriter($exportFilepath, $cols);
  
  file_put_contents($exportFilepath, join(';', $cols) . PHP_EOL);
  
  $products = Mage::getModel('catalog/product')->getCollection();//Mage::getResourceModel('catalog/product_collection');
        foreach($filters as $field => $condition){
            $products->addAttributeToFilter($field, $condition);
        }
        
        $products->addAttributeToSelect(array_keys($fields));
        $i=0;
        foreach($products as $product){
        //if ($i++ == 20) break;
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
            //$row = array_intersect_key($product->getData(),$fields); 
            $row = array_fill_keys($cols,'');
            //Zend_Debug::dump($product->getData());
            /*foreach($row as $key => $value){
              echo "$key, $value<br/>";
              if ($fields[$key])
                $row[$key] = $product->getAttributeText($key);
            }*/
            foreach($fields as $key => $isSelect){
                $row[$key] = $isSelect ? $product->getAttributeText($key) : $product->getData($key);
            }
            
            $row['image'] = (!in_array($product->getImage(),array('','no_selection'))) ? ($urlBase . 'image.php?id='.$product->getId()) : '';// 'media/catalog/product'.$product->getImage()) : '';
            $row['model'] = $product->getName(); //iconv("UTF-8", "Windows-1250", $product->getName());

            $row['manufacturer'] = '';
            if(strstr($product->getName(),"TRISTAR")) $row['manufacturer'] = 'TRISTAR';
            else $row['manufacturer'] = 'WSP'; 
            
            //echo $row['sku'].': '.join(',',$product->getCategoryIds()).' ... ';
            $row['car_brand'] = '';
            foreach($product->getCategoryIds() as $catId){
              $category = Mage::getModel('catalog/category')->load($catId);
              //echo $row['sku'].': '.$category->getLevel().' - '. $category->getName().'<br/>';
              if ($category->getLevel()!= 3)
                continue;
              $row['car_brand'] = str_replace('Ё','E',$category->getName()); //iconv("UTF-8", "Windows-1250", $category->getName());
              break;
            }
            $row['is_in_stock'] = $stockItem->getIsInStock();
            $row['qty'] = (int)$stockItem->getQty();
            //echo Mage::helper('core')->currency($row['price'] / 1.2,false,false).' '.Mage::helper('core')->currency($row['price'] / 1.2,true,false).'<br/>';
            $row['price'] = round(Mage::helper('core')->currency($row['price']/* / 1.2*/,false,false)/1.21,2);///*(int)*/ ($row['price'] / 1.2);
            $row['your price'] = (isset($prices[$product->getId()]))? /*(int)*/ round(Mage::helper('core')->currency($prices[$product->getId()],false,false)/1.21,2) : '';
            unset($row['name']);
            unset($row['category_ids']);
            //$exportCsv->save_row($row);
            
            foreach($row as $att => $val){
              $row[$att] = str_replace(';',',',$val);
            }
            
            
            file_put_contents($exportFilepath, join(';', $row) . PHP_EOL, FILE_APPEND);
            
        }
}        
    //$exportCsv->close(); 
 exit;   
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$exportName.'"');
		readfile($exportFilepath);

