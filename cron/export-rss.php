<?php
	chdir(dirname(__FILE__).'/..');
    require_once 'app/Mage.php';
    umask( 0 );
    Mage::app(); 


        function getgoogleCategory($product){
        /*foreach(Zebu_Mage_ProductHelper::get_category_max_pathes_array(join(',',$product->getCategoryIds()),' / ',0) as $cat){
            $cat = str_replace('|','/',$cat);
            if (isset($GLOBALS['category_map'][$cat])){
                return $GLOBALS['category_map'][$cat];
            }
            Mage::log('neznam google kategorii pro '.$cat);
            Mage::log('catmap: '.count($GLOBALS['category_map']));
        }*/
        return 'Google Cat...';
    }
    
    function categoryMap($product) {
        /*$ids = $product->getCategoryIds();

        if(in_array(11, $ids) || in_array(147, $ids)) {
            return "Zdraví a krása > Zdravotní péče > Fitness a výživa";
        } else if(in_array(60, $ids)) {
            return "Sportovní potřeby";
        } else {
            return "Oblečení a doplňky > Oblečení";
        } */
        //Dům a zahrada > Rostliny > Květiny

        return 'Umění a zábava > Párty a oslavy > Dary > Řezané květiny'; 
    }
        /*function short($string,$limit){
            if (mb_strlen($string,'UTF-8')<=$limit) return $string;
            $string = mb_substr($string, 0, $limit-3, 'UTF-8').'...';
            return $string;
        }*/
        /*Mage :: app( "cz" ) -> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
        $userModel = Mage::getModel('admin/user');
        $userModel->setUserId(0);
        Mage::getSingleton('admin/session')->setUser($userModel);
        */
        //Mage :: app( "cz" ) -> setCurrentStore( 1 );
        
        function short($string, $limit){
            //$string = strip_tags(htmlspecialchars_decode(html_entity_decode($string)));
            //Zebu_Auxiliary::info_message($string);
            $string = strip_tags(html_entity_decode($string,ENT_QUOTES,"UTF-8"));
            //Zebu_Auxiliary::info_message($string,1);
            
            if (mb_strlen($string,'UTF-8')<=$limit) return htmlspecialchars($string);
            $string = mb_substr($string, 0, $limit-3, 'UTF-8').'...';
            //Zebu_Auxiliary::info_message($string,2);
            return htmlspecialchars($string);
        }
        define('_URLBASE_',Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
        Zebu_Mage_ProductHelper::set_quoting(false);
        
        $groupId = 0;//1
        $websiteId = Mage::app()->getStore()->getWebsiteId();
       
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        		$select = $connection
                            ->select()
//                            ->from('catalog_product_index_price')
                            ->from('catalog_product_index_price')
                            ->where('customer_group_id = '.(int) $groupId)
                            //->where('website_id = '.(int) $websiteId)
                            //->where('attribute_id = 60');
                            ;
        		$prices = array();
        		foreach($connection->query($select) as $priceRow){
        		  if (isset($prices[$priceRow['website_id']][$priceRow['entity_id']])){
                   $prices[$priceRow['website_id']][$priceRow['entity_id']] = min($prices[$priceRow['website_id']][$priceRow['entity_id']], $priceRow['final_price']);
              }else
        			     $prices[$priceRow['website_id']][$priceRow['entity_id']] = $priceRow['final_price'];
        		}
        		$GLOBALS['prices']=$prices;
        		//file_put_contents('zlog.txt',print_r($GLOBALS['prices'],1));
        		
        		
        		
        		function convertPrice($price, $ceil = true){
        		    /*$baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
                $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
                $price = Mage::helper('directory')->currencyConvert($price, $baseCurrencyCode, $currentCurrencyCode);
                $price = Mage::app()->getStore()->roundPrice($price);*/
                if($ceil)
                    return ceil($price); 
                else
                    return $price;
            }
        		
            function getProductPrice($product, $ceil = true){
                //global $prices;
                $prices = $GLOBALS['prices'];
                //file_put_contents('zlog.txt',count($prices).'-'.$product->getId().PHP_EOL, FILE_APPEND);
                if (isset($prices[Mage::app()->getStore()->getWebsiteId()][$product->getId()])){
                    return /*ceil*/convertPrice($prices[ Mage::app()->getStore()->getWebsiteId()][$product->getId()], $ceil);
                }
                return /*ceil*/convertPrice($product->getPrice(), $ceil);
                
                
                
                //return ($product->getSpecialPrice()) ? ceil($product->getSpecialPrice()) : ceil($product->getPrice());
            }

header('Content-Type: text/xml');
$xml = '<?xml version="1.0" encoding="utf-8"?>';
$xml .=  '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
      <title>Product export</title>
      <link>' . Mage::getBaseUrl() . 'export/rss.xml</link>
      <description>Product export</description>';

      


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
           /*$xml .= '
    <PRODUCT>
      <SKU>'.$product->getSku().'</SKU>
      <EAN>'.$product->getEan().'</EAN>
      <PRICE>'.$_taxHelper->getPrice($product, $product->getFinalPrice()).'</PRICE>
      <AVAILABILITY>'.$product->getAttributeText('availability').'</AVAILABILITY>
    </PRODUCT>';*/
        ob_start();
        ?>
        <item>
        <g:id><?php echo $product->getSku();?></g:id>
        <title><?php echo short($product->getName(), 70); ?></title>
        <description><?php echo short($product->getDescripion(),512); ?></description>
        <g:google_product_category><?php echo getGoogleCategory($product); ?></g:google_product_category>

        <?php foreach(Zebu_Mage_ProductHelper::get_category_max_pathes_array(join(',',$product->getCategoryIds()),' | ',0) as $cat){ ?>
        <g:product_type><?php echo htmlspecialchars($cat); ?></g:product_type>
        <?php break; } ?>

        <link><?php echo Mage::app()->getStore()->getBaseUrl().$product->getUrlPath(); ?></link>
        <?php if (!preg_match('~^(no_selection)?$~',$product->getImage())) : ?> 
        <g:image_link><?php echo Mage::app()->getStore()->getBaseUrl().'media/catalog/product'.$product->getImage() ?></g:image_link>
        <?php endif; ?>
        <g:condition>new</g:condition>

        <?php if(Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty() > 0 ) : ?>
        <g:availability>in stock</g:availability>
        <?php else: ?>
        <g:availability>available for order</g:availability>
        <?php endif;?>
        <g:price><?php echo getProductPrice($product); ?></g:price>
        <g:brand><?php echo $product->getAttributeText('manufacturer'); ?></g:brand>
        <?php if(trim($product->getEan()) != "") { ?>
        <g:gtin><?php echo $product->getEan(); ?></g:gtin>
        <?php } ?>
        <g:mpn><?php echo $product->getSku(); ?></g:mpn>
    </item>
    <?php
     $xml .= ob_get_clean();
    
    if($product->getTypeId() == "configurable"):
    $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
    
    $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
    foreach($simple_collection as $simple_product){
      //echo $simple_product->getSku() . " - " . $simple_product->getName() . " - " . Mage::helper('core')->currency($simple_product->getFinalPrice()) . "<br>";
      /*$xml .= '
    <PRODUCT>
      <SKU>'.$simple_product->getSku().'</SKU>
      <EAN>'.$simple_product->getEan().'</EAN>
      <PRICE>'.$_taxHelper->getPrice($simple_product, $simple_product->getFinalPrice()).'</PRICE>
      <AVAILABILITY>'.$simple_product->getAttributeText('availability').'</AVAILABILITY>
    </PRODUCT>';*/
    
            ob_start();
        ?>
        <item>
        <g:id><?php echo $simple_product->getSku();?></g:id>
        <title><?php echo short($simple_product->getName(), 70); ?></title>
        <description><?php echo short($product->getDescripion(),512); ?></description>
        <g:google_product_category><?php echo getGoogleCategory($product); ?></g:google_product_category>

        <?php foreach(Zebu_Mage_ProductHelper::get_category_max_pathes_array(join(',',$product->getCategoryIds()),' | ',0) as $cat){ ?>
        <g:product_type><?php echo htmlspecialchars($cat); ?></g:product_type>
        <?php break; } ?>

        <link><?php echo Mage::app()->getStore()->getBaseUrl().$product->getUrlPath().'#'.$simple_product->getId(); ?></link>
        <?php if (!preg_match('~^(no_selection)?$~',$product->getImage())) : ?> 
        <g:image_link><?php echo Mage::app()->getStore()->getBaseUrl().'media/catalog/product'.$product->getImage() ?></g:image_link>
        <?php endif; ?>
        <g:condition>new</g:condition>

        <?php if(Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple_product)->getQty() > 0 ) : ?>
        <g:availability>in stock</g:availability>
        <?php else: ?>
        <g:availability>available for order</g:availability>
        <?php endif;?>
        <g:price><?php echo getProductPrice($simple_product); ?></g:price>
        <g:brand><?php echo $product->getAttributeText('manufacturer'); ?></g:brand>
        <?php if(trim($simple_product->getEan()) != "") { ?>
        <g:gtin><?php echo $simple_product->getEan(); ?></g:gtin>
        <?php } ?>
        <g:mpn><?php echo $simple_product->getSku(); ?></g:mpn>
    </item>
    <?php
     $xml .= ob_get_clean();
    
      //'$_taxHelper->getPrice($simple_product, $simple_product->getFinalPrice()).'<hr />';
    }
    endif;
    }
    
    $xml .= '    </channel>
  </rss>';    
    /*$p = Mage::getModel('catalog/product')->load(561); 
    echo '#'.$product->getTypeInstance(true)->getFinalPrice(1, $p);*/
    
    file_put_contents('export/rss.xml', $xml);
    
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

