<?php
    /*$GLOBALS['category_map'] = array();
    $csv = new Zebu_Tools_CSV_AssocReader(dirname(__FILE__).'/categorymap.csv');
    while($row = $csv->getRow()){
       $GLOBALS['category_map'][$row['kuptotu']] = $row['google'];
    }
    Mage::log($GLOBALS['category_map']);*/
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
                return /*ceil*/convertPrice($product->getPrice() + ($product->getPrice() * 20/100), $ceil);
                
                
                
                //return ($product->getSpecialPrice()) ? ceil($product->getSpecialPrice()) : ceil($product->getPrice());
            }
		
		
?>