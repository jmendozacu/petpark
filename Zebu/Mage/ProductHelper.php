<?php

/**
 * Zebu_Mage_ProductHelper
 * Pomocna trida. Slouzi napr. k ziskavani cest kategorii, odstraneni produktu vcetne obrayku,
 * ziskavani top produktu atd.
 *
 * @author Ondřej Kohut
 */
class Zebu_Mage_ProductHelper {
    private static $top_products_categories;// = array();
    protected static $is_quoting_set = true;

    protected static $connection_read;
    protected static $connection_write;
    
    const DEFAULT_DELIMITER = '/';

    public static function set_quoting($value = true){
        self::$is_quoting_set = $value;
    }
    /*public static function obtain_top_categories() {
        $top_categories = array();
        $store_names = Snakup::get_store_names();
        $store_names['mobilni_telefony'] = "Mobilní telefony";
        foreach($store_names as $entity => $name) {
            Zebu_Auxiliary::info_message($entity,2);

            Mage::app()->setCurrentStore($entity);
            $categories = Mage::getModel('catalog/category')->getCollection()
                //->addAttributeToFilter('name',array('in', array('Nejprodávanější', 'Hlavn%')))//pak opravit tu i v tompmobilu
                //->addAttributeToFilter('name',array('in' => array('Nejprodávanější', 'Hlavn%')))//pak opravit tu i v tompmobilu
                ->addAttributeToFilter('name',array('like' => 'Hlavn%'))//pak opravit tu i v tompmobilu
                ->addAttributeToSelect('name')
                //->addAttributeToFilter('store_id',Snakup::get_store_id($entity))
                ->load();;

            foreach($categories as $cat) {
                Zebu_Auxiliary::info_message($cat->getName().'_'.$cat->getId());
                if ($cat->getParentCategory()->getName()==$name) {
                    $top_categories[$entity] = $cat->getId();
                }
            }
        }
    }*/

    public static function set_top_products_categories($top_products_categories_array)
    {
        self::$top_products_categories = $top_products_categories_array;
    }

    public static function obtain_top_product_categories() {
        self::$top_products_categories = array();

        foreach(Mage::app()->getStores() as $store) {
            if ($store->getCode()=="default")
                continue;
           
            Mage::app()->setCurrentStore($store);
            $root_cat_id = $store->getRootCategoryId();

            $categories = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToFilter(//array(
                'name', array('or' => array(
                array('like' => 'Nejprod%'), //u vetsiny aktegorie Nejprodavanejsi
                array('like' => 'Hlavn%'), //z topmobilu v kategorii Hlavni stranka
                array('like' => 'Nejobl%') //z alu v kategorii Nejoblibenejsi
                )
                )
                )
                ->addAttributeToSelect('name')
                ->load();;

            foreach($categories as $cat) {
                //Zebu_Auxiliary::info_message($cat->getName().'_'.$cat->getId().', parent:'.$cat->getParentId());
                if ($cat->getParentId()==$root_cat_id) {
                    self::$top_products_categories[$store->getCode()] = $cat->getId();
                }
            }
        }
        //Zebu_Auxiliary::info_message('Nacteno (top): '.print_r(self::$top_products_categories,true), 1, '../var/log/log_tmp.txt');
    }


    public static function get_imported_top_products($store, $count) {
        if (!isset(self::$top_products_categories))
            self::obtain_top_product_categories();

        $best_selling_products = array();

        if (!isset(self::$top_products_categories[$store]))
            return array();

        $cat_id = self::$top_products_categories[$store];
        Mage::app()->setCurrentStore($store);

        $category = Mage::getModel('catalog/category')->load($cat_id);
        $products = $category->getProductCollection()
            ->addAttributeToSelect(array('sku', /*'category_ids',*/ 'name', 'price', 'small_image', 'short_description', 'url_key', 's_store_entity'/*'description'*/));

        $product_array = array();
        foreach($products as $p) {
            $product_array[] = $p;
        }


        //chceme vybrat nahodne prvky, kdyz ma pole mene, tak mene
        $random_products_count = min(array( $count, count($product_array)));
        if ($random_products_count==0) return array();

        $random_keys = array_rand($product_array, $random_products_count);

        if (!is_array($random_keys))
            $random_keys = array($random_keys);

        foreach ($random_keys as $key) {
            $best_selling_products[] = $product_array[$key];
        }

        return $best_selling_products;
    }

  /*  public static function get_top_product_collection($products_per_store = 2) {
        //$c = new Mage_Core_Model_Abstract();
        $products = Mage::getModel('catalog/product');
        //$products->
        $c = new Varien_Object();
        $c->addData(self::get_top_products($products_per_store));
        //$c->
        
//        $c = new Varien_Data_Collection();
        return $c;
        
    }*/

    public static function get_store_top_products($store, $count) {
        $_product_array = Zebu_Mage_ProductHelper::get_imported_top_products($store, $count);//get_top_products(2);
        //if (count($_product_array)<$count)
        //    $_product_array = array_merge($_product_array, Zebu_Mage_ProductHelper::get_magento_top_products($store, $count - count($_product_array)));
        
        return $_product_array;

    }

    public static function get_magento_top_products($store, $count) {
        Mage::app()->setCurrentStore($store);
        //Mage::app('admin', 'store');
        $store_id = Snakup::get_store_id($store);
        $best_selling_products = array();
      /*  $products = Mage::getResourceModel('reports/product_collection')
            //->addAttributeToFilter('sku',array('like' => $store.'%'))
            ->addAttributeToFilter('s_store_entity',$store)
            //->addAttributeToSelect('*');
           //->addAttributeToSelect(array('sku', /*'category_ids',* / 'name', 'price', 'small_image', 'short_description', /*'description'* /));
           ->addAttributeToSelect(array('sku', /*'category_ids',* / 'name', 'price', 'small_image', 'short_description', 'url_key', 's_store_entity'/*'description'* /));

*/
        Zebu_Auxiliary::info_message('get magento top products for '.$store);
        $visibility = array(
                      Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                      Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
                  );

        $storeId = $store_id;//Mage::app()->getStore()->getId();

        //$products = Mage::getResourceModel('reports/product_collection')
        $products = Mage::getResourceModel('reports/product_viewed_collection')
            //->addOrderedQty()
           // ->addSStoreEntity()
           //    ->addAttributeToFilter('s_store_entity',$store)
           // ->addAttributeToSelect('*')
            ->addAttributeToFilter('s_store_entity',$store)
            ->addAttributeToFilter('sku',array('like' => $store.'%'))
            ->addAttributeToFilter('small_image',array('neq' => ''))
            ->addAttributeToSelect(array('name', 'price', 'small_image', 's_store_entity', 'sku')) //edit to suit tastes
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            
         ;

       /*$products =  Mage::getResourceModel('reports/product_collection')
            ->addAttributeToSelect('*')
            ->addViewsCount()
            ->getSelect()->limit((int)$count)->query();*/
         //   ->addViewsCount();
/*
        $products = Mage::getModel('catalog/product')->getResourceCollection()
                //   ->addOrderedQty()
            //->addAttributeToFilter('s_store_entity',$store)
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name', 'price', 'small_image', 's_store_entity', 'sku')) //edit to suit tastes
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->load()
         ;*/


             /*                // ->addViewsCount()
                              ->addAttributeToFilter('s_store_entity',$store)
                              ->addAttributeToFilter('sku',array('like' => $store.'%'))
                              ->addAttributeToSelect('*')
                             // ->addOrderedQty()
                              ->addAttributeToFilter('visibility', $visibility)
                              ->addStoreFilter($storeId)
                             ;// ->setOrder('views_count', 'desc');
                              //->setOrder('ordered_qty', 'desc');
*/
        /*$storeId    = Mage::app()->getStore()->getId();

        $products = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name', 'price', 'small_image')) //edit to suit tastes
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addViewsCount();*/

        $i=0;
        foreach ($products as $product) {

            if (++$i > $count) break;
            $best_selling_products[] = $product;// array('sku' =>  $product->getSku(),'name' => $product->getName);//$product;
        //      Zebu_Auxiliary::info_message('Assign product '.$product->getName().'('.$product->getId().') to category '.Snakup0::$best_selling_categories[$store]);
        }
        return $best_selling_products;
    }

    //nefunguje
   /* public static function assign_top_products_to_top_category($category_id = 2620, $products_per_store = 2){
       
/*
       $api = new Zebu_Mage_API();
       $r = $api->call('catalog_category.info', array('2620'));
       print_r($r);
       return; * /

       $top_products = self::get_top_products($products_per_store);

       Zebu_Auxiliary::info_message(count($top_products));
//$top_category = Mage::getModel('catalog/category')->load($cat_id);

       Mage :: app('default')-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
       $api = new Zebu_Mage_API();
       $r = $api->call('catalog_category.info', array($category_id));
       print_r($r);

       $i=0;
       foreach ($top_products as $product) {
           Zebu_Auxiliary::info_message('assign '.$product->getname().' ('.$product->getSku().')');
           $api->call('catalog_category.assignProduct', array($category_id, (string)$product->getSku(), $i++));
       }
    }*/

    public static function get_top_products0($products_per_store = 2) {
        Mage :: app('default')-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
        $top_products = array();
        foreach (self::$top_products_categories as $store => $cat) {
            //Zebu_Auxiliary::start_info_timer('imported');
            $store_top_products = self::get_imported_top_products($store, $products_per_store);
            //Zebu_Auxiliary::stop_info_timer('imported');
            //Zebu_Auxiliary::info_message($store.' imported: '.count($store_top_products));
            if (count($store_top_products)==0) {
            //Zebu_Auxiliary::start_info_timer('magento');
                $store_top_products = self::get_magento_top_products($store, $products_per_store);
            //Zebu_Auxiliary::stop_info_timer('magento');
            //Zebu_Auxiliary::info_message($store.' top magento: '.count($store_top_products));
            }

            $top_products = array_merge($top_products, array_values($store_top_products));

            /*foreach ($top_products as $i => $product) {
                Zebu_Auxiliary::info_message($i.' '.$store.': '.$product->getSku().', '.$product->getName(),1);
            }

            foreach ($store_top_products as $i => $product) {
                Zebu_Auxiliary::info_message($i.' '.$store.': '.$product->getSku().', '.$product->getName(),2);
            }*/
        }
        return $top_products;
    }

    static protected $category_pathes = array();

    public static function get_all_category_path_array(){
        return self::$category_pathes;
    }

    public static function get_category_max_pathes_string($string_cat_ids, $delimiter = self::DEFAULT_DELIMITER){
        $cats = self::get_category_max_pathes_array($string_cat_ids, $delimiter);
        return join(', ', $cats);
    }

    public static function get_category_max_pathes_array($string_cat_ids, $delimiter = self::DEFAULT_DELIMITER, $starting_level = 0){
        //Zebu_Auxiliary::info_message($string_cat_ids.', del:'. $delimiter);
        $category_pathes = self::get_category_pathes_array($string_cat_ids, $delimiter, $starting_level);

        //Zebu_Auxiliary::info_message(print_r($category_pathes,true));
        foreach ($category_pathes as $i => $val){
            if (empty($val)){
                unset($category_pathes[$i]);
                continue;
            }
            foreach ($category_pathes as $j => $val2) {
                if ($i == $j)
                    continue;
                //Zebu_Auxiliary::info_message($val.', '.$val2);

                if (mb_strstr($val2, $val)){
                    unset($category_pathes[$i]);
                    //Zebu_Auxiliary::info_message('mazu: '.$val.', obsazeno ve '.$val2);
                    break;
                }
            }
        }
        return $category_pathes;
    }

    public static function get_category_pathes_array($string_cat_ids, $delimiter = self::DEFAULT_DELIMITER, $starting_level = 0){
        $category_pathes = array();//explode(',', $string_cat_ids);

        foreach (explode(',', $string_cat_ids) as $cat_id) {
            //echo $cat_id.'<br/>';
            $cat_id = trim($cat_id);
            $category_pathes[$cat_id] = self::get_category_path($cat_id, $delimiter, $starting_level);
        }

        return $category_pathes;
    }

    //public static function

    public static function get_category_path($cat_id, $delimiter = self::DEFAULT_DELIMITER, $starting_level = 0) {
        if (isset(self::$category_pathes[$cat_id])){
            return self::$category_pathes[$cat_id];
        }

        $cat = new Mage_Catalog_Model_Category();
        $cat->load($cat_id);

        $categories = array();
        $root = true;
        foreach($cat->getParentCategories() as $c) {
            /*if ($root) {
                $root = false;
                continue;
            }*/
            //echo $c->getName().': '.$starting_level.'/'.$c->getLevel().' * ';
            
            if (!($c->getParentId()>1))
                continue;
            
            if ($c->getLevel() < $starting_level+2){ 
                continue;
            }
            
                
            if (self::$is_quoting_set)
                $categories[$c->getLevel()] = self::quote($c->getName());
            else
                $categories[$c->getLevel()] = $c->getName();
        }
        
        ksort($categories);
        
/*
        for($i=0;$i<$starting_level;$i++){
            unset($categories[$i]);
        }*/

        $category_path = join($delimiter, $categories);
        self::$category_pathes[$cat_id] = $category_path;
        return $category_path;
    }

    public static function get_category_pathes($string_cat_ids, $delimiter = self::DEFAULT_DELIMITER, $starting_level = 0){
        /*$category_pathes = array();//explode(',', $string_cat_ids);

        foreach (explode(',', $string_cat_ids) as $cat_id) {
            //echo $cat_id.'<br/>';
            $cat_id = trim($cat_id);
            $category_pathes[$cat_id] = self::get_category_path($cat_id, $delimiter);
        }

        return join(', ', $category_pathes);*/
        return join(', ', self::get_category_pathes_array($string_cat_ids, $delimiter));
    }

    protected static function quote($string) {
        return '"'.addslashes(trim($string)).'"';
    }

    public static function create_top_product_import_sheet($filename, $count = 4) {
        if (!isset(self::$top_products_categories))
            self::obtain_top_product_categories();
            
        Mage::app('admin', 'store');

        //$api = new Zebu_Mage_API();

        $import_creator = new Zebu_Tools_XmlSheetCreator($filename);
        $import_creator->set_attributes(array('store','category_ids','sku'));
        $import_creator->write_attribute_names_row();

        foreach(self::$top_products_categories as $entity => $cat_id) {
            Zebu_Auxiliary::info_message($entity);

            Mage::app()->setCurrentStore($entity);

            $cat = Mage::getModel('catalog/category')->load($cat_id);
            
            foreach($cat->getProductCollection() as $p) {
            //print_r($p);
                $p = Mage::getModel('catalog/product')->load($p->getId());
                Zebu_Auxiliary::info_message('___ '.$p->getName().', '.$p->getSku(),1);
            }

            if (count($cat->getProductCollection())<$count) {
                Zebu_Auxiliary::info_message('Pridat produkty:'. ($count - count($cat->getProductCollection())));
                $top_products = self::get_magento_top_products($entity, $count);
                // - count($cat->getProductCollection()));
                //cely $count, pro propad ,ze uz je obsazeno, aby bylo min. $count ruznych

                foreach($top_products as $product) {
                    $category_ids = $product->getCategoryIds();
                    $category_ids[] = $cat->getId();
                    $import_creator->write_row(array(
                        'store' => $entity,
                        'category_ids' => join(',', $category_ids),
                        'sku'   => (string)$product->getSku()
                    ));
                 }
            }
        }
        $import_creator->close();
    }

    public static function create_store_top_product_import_sheet($entity, $filename, $count = 4) {
        if (!isset(self::$top_products_categories))
            self::obtain_top_product_categories();

        Mage::app('admin', 'store');

        //$api = new Zebu_Mage_API();

        $import_creator = new Zebu_Tools_XmlSheetCreator($filename);
        $import_creator->set_attributes(array('store','category_ids','sku'));
        $import_creator->write_attribute_names_row();

        $cat_id = self::$top_products_categories[$entity];
        //foreach(self::$top_products_categories as $entity => $cat_id) {

        Zebu_Auxiliary::info_message($entity);

        Mage::app()->setCurrentStore($entity);

        $cat = Mage::getModel('catalog/category')->load($cat_id);

        foreach($cat->getProductCollection() as $p) {
        //print_r($p);
            $p = Mage::getModel('catalog/product')->load($p->getId());
            Zebu_Auxiliary::info_message('___ '.$p->getName().', '.$p->getSku(),1);
        }

        if (count($cat->getProductCollection())<$count) {
            Zebu_Auxiliary::info_message('Pridat produkty:'. ($count - count($cat->getProductCollection())));
            $top_products = self::get_magento_top_products($entity, $count);
            Zebu_Auxiliary::info_message(count($top_products));
            // - count($cat->getProductCollection()));
            //cely $count, pro propad ,ze uz je obsazeno, aby bylo min. $count ruznych

            foreach($top_products as $product) {
                Zebu_Auxiliary::info_message('___ '.$product->getName().', '.$product->getSku(),1);
                $category_ids = $product->getCategoryIds();
                $category_ids[] = $cat->getId();
                $import_creator->write_row(array(
                    'store' => $entity,
                    'category_ids' => join(',', $category_ids),
                    'sku'   => (string)$product->getSku()
                ));
            }
        }

        $import_creator->close();
    }

    /*public function get_configurable_product($child){
  //      get
    }*/
    public function get_configurable_attributes($product){
        $resource = Mage :: getSingleton( 'core/resource' );
        $connection = $resource->getConnection('core_write');

        $select = $connection
            ->select()
            ->from($super_attribute_table)
            ->where('product_id = ?', $product->getId());

        //echo $super_attribute_table.' - '.$parent->getId().', '.$attribute->getId();
        $super_attributes = $connection->fetchAll($select);

        $c = new Mage_Catalog_Model_Product_Type_Configurable();

        $c->getUsedProductAttributeIds($product);

    }

    public function configurable_product_link($parent, $child, $attribute) {

        $price_dif = $child->getPrice() - $parent->getPrice();

        $resource = Mage :: getSingleton( 'core/resource' );
        $product_table = $resource -> getTableName( 'catalog/product' );
        $attribute_table = $resource -> getTableName( 'eav/attribute' );
        $attribute_value_table = $resource -> getTableName( 'eav/attribute_option_value' );
        $attribute_option_table = $resource -> getTableName( 'eav/attribute_option' );
        $super_link_table = $resource -> getTableName( 'catalog/product_super_link' );
        $super_attribute_table = $resource -> getTableName( 'catalog/product_super_attribute' );
        $super_attribute_pricing_table = $resource -> getTableName( 'catalog/product_super_attribute_pricing' );
        $super_attribute_label_table = $resource -> getTableName( 'catalog/product_super_attribute_label' );

        $connection = $resource->getConnection('core_write');

        //ziskani ID pro hodnotu konfigurovatelneho atributu i childa
        $select = $connection
            ->select()
            // ->from($attribute_value_table)
            ->from(array('value_table' => $attribute_value_table)/*,
                    array('value_id', 'option_id', 'value')*/)
            ->join(array('option_table' => $attribute_option_table), 'value_table.option_id = option_table.option_id')
            ->where('attribute_id = ?', $attribute->getId())
            ->where('value = ?', $child->getAttributeText('s_saty_c_size'));

        $row = $connection->fetchRow($select);
        $value_index = $row['option_id'];
        //echo $value_index;
        //return;

        //Zebu_Auxiliary::info_message('value_index: '.$value_index);
        //test na prirazeni konfigurovatelneho atributu k parentovi
        //product_super_attribute_id 	product_id 	attribute_id 	position
        $select = $connection
            ->select()
            ->from($super_attribute_table)
            ->where('product_id = ?', $parent->getId())
            ->where('attribute_id = ?', $attribute->getId());

        //echo $super_attribute_table.' - '.$parent->getId().', '.$attribute->getId();
        $super_attribute_id = $connection->fetchOne($select); //fetchOne( "select * from $product_table where sku='" . $importData['sku'] . "'" );
        //print_r($super_attribute_id);

        //konf. atribut neni u parenta definovat, vytvorime
        if (empty($super_attribute_id)) {
            $query = sprintf('INSERT INTO %s VALUES (NULL, %d, %d, 0)',$super_attribute_table,$parent->getId(),$attribute->getId());
            Zebu_Auxiliary::info_message($query);
            $connection->query($query);
            $super_attribute_id = $connection->fetchOne($select);
            print_r($super_attribute_id);
        }
        //Zebu_Auxiliary::info_message('$super_attribute_id: '.$super_attribute_id);

        //test na definice zmeny cen pro dany konf. atribut a hodnotu
        // 	value_id 	product_super_attribute_id 	value_index 	is_percent 	pricing_value 	website_id
        $select = $connection
            ->select()
            ->from($super_attribute_pricing_table)
            ->where('product_super_attribute_id = ?', $super_attribute_id)
            ->where('value_index = ?', $value_index);

        $value_id = $connection->fetchOne($select);
        if (empty($value_id)) {
            $query = sprintf('INSERT INTO %s VALUES (NULL, %d, %d, 0, %f, 0)',
                $super_attribute_pricing_table, $super_attribute_id, $value_index, $price_dif);
        }
        else {
            $query = sprintf('UPDATE %s SET pricing_value = %f WHERE product_super_attribute_id = %d AND value_index = %d',
                $super_attribute_pricing_table, $price_dif, $super_attribute_id, $value_index);
        }
        $connection->query($query); //aktualizuj zmeny cen
        //Zebu_Auxiliary::info_message('pricing: '.$query);

        //test na definice labelu pro dany konf. atribut
        //value_id 	product_super_attribute_id 	store_id 	value
        $select = $connection
            ->select()
            ->from($super_attribute_label_table)
            ->where('product_super_attribute_id = ?', $super_attribute_id);

        //Zebu_Auxiliary::info_message('label start');
        $value_id = $connection->fetchOne($select);
        //Zebu_Auxiliary::info_message('label value_id: '.$value_id);
        if (empty($value_id)) {
            $query = sprintf('INSERT INTO %s VALUES (NULL, %d, 0, %s)',
                $super_attribute_label_table, $super_attribute_id, $attribute->getFrontendLabel());
        }
        else {
            $query = sprintf('UPDATE %s SET `value` = "%s" WHERE product_super_attribute_id = %d',
                $super_attribute_label_table,  $attribute->getFrontendLabel(), $super_attribute_id);
            Zebu_Auxiliary::info_message('labeling: '.$query);
        }
        $connection->query($query); //aktualizuj zmeny cen

        //Zebu_Auxiliary::info_message('label: '.$query);

        //test na existenci linku mezi parentem a childem
        $select = $connection
            ->select()
            ->from($super_link_table)
            ->where('product_id = ?', $child->getId())
            ->where('parent_id = ?', $parent->getId());

        $link_id = $connection->fetchOne($select);

        if (empty($link_id)) {
            $query = sprintf('INSERT INTO %s VALUES (NULL, %d, %d)',
                $super_link_table, $child->getId(), $parent->getId());
            $connection->query($query); //vytvor link
        }

    //Zebu_Auxiliary::info_message('insert: '.$query);


    }


function import_tier_price($sku, $tier_prices){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $product_id = Mage::getModel('catalog/product')->getIdBySku($sku);

        $tier_price_table = Mage :: getSingleton( 'core/resource' )
            -> getTableName( 'catalog/product' ) . '_tier_price';

            $select = $connection
                    ->select()
                    ->from($tier_price_table)
                    ->where('`entity_id` = '.$product_id);

            $rows = $connection->fetchAll($select);

            foreach($rows as $row){
                //Zebu_Auxiliary::info_message($row['qty'].' | '.$row['value'].' | '.$row['customer_group_id'].' | '.$row['all_groups'].' | '.$row['website_id'],2);
                //if (!isset($tier_prices[$row[''].':'.]))
                $exists = false;
                foreach($tier_prices as $key => $tier_price){
                    if ($row['qty']==$tier_price['qty']
                        && $row['customer_group_id']==$tier_price['customer_group_id']
                        && $row['website_id']==$tier_price['website_id']){
                            
                            $all_groups = ($tier_price['customer_group_id']==0)?1:0;
                            //je obsazeno v importu, bude update
                            //Zebu_Auxiliary::info_message('je obsazeno v importu, bude update');
                            $sql = sprintf("UPDATE %s
                                SET `value` = %s, `all_groups` = %d WHERE `entity_id` = %d AND `qty` = %s AND `customer_group_id` = %d AND `website_id` = %d" ,
                                $tier_price_table,
                                $tier_price['price'],
                                $all_groups,
                                $product_id,
                                $tier_price['qty'],
                                $tier_price['customer_group_id'],
                                $tier_price['website_id']
                                );
                                //odstranim zpracovane, co zbude pak insertnu
                                unset($tier_prices[$key]);
                            
                            $exists = true;
                            break;
                    }
                }

                 if (!$exists){
                            //neni v importu, smazu
                            $sql = sprintf("DELETE FROM %s
                                WHERE `entity_id` = %d AND `qty` = %s AND `customer_group_id` = %d AND `website_id` = %d " ,
                                $tier_price_table,
                                $product_id,
                                $row['qty'],
                                $row['customer_group_id'],
                                $row['website_id']);
                        }
                       //Zebu_Auxiliary::info_message($sql);
                       $data = $connection->query($sql);

                }
                foreach($tier_prices as $key => $tier_price){
                    $all_groups = ($tier_price['customer_group_id']==0)?1:0;
                    //nebylo v tabulce, bude insert
                    $sql = sprintf("INSERT INTO %s
                            (`entity_id`, `all_groups`, `customer_group_id`,`qty`, `value`, `website_id`)
                            VALUES ('%s', %d, %d, %s, %s, %d)",
                            $tier_price_table,
                            $product_id,
                            $all_groups,
                            $tier_price['customer_group_id'],
                            $tier_price['qty'],
                            $tier_price['price'],
                            $tier_price['website_id']
                            );
                    //Zebu_Auxiliary::info_message($sql);
                    $data = $connection->query($sql);
                }
            }

            public static function delete_product_with_images_by_sku($sku){
                //$product = new Mage_Catalog_Model_Product();
                //$product = $product->loadByAttribute('sku', $sku);

                $product = Mage::getModel('catalog/product')->load(Mage::getModel('catalog/product')->getIdBySku($sku));
                self::delete_product_with_images($product);
            }

            public static function delete_product_with_images($product){
                $images = $product->getMediaGalleryImages();
                //Zebu_Auxiliary::info_variable($images);
                foreach($images as $image){
                    //Zebu_Auxiliary::info_variable($image);
                    //Zebu_Auxiliary::info_message($image->getPath());
                    if (!@unlink($image->getPath()))
                        Zebu_Auxiliary::info_message(__CLASS__.': Cannot delete '.$image->getPath(),2);
                }
                $product->delete();

                //echo $product->getId().'<hr/>';
                //print_r($product->getData());
                /*$media_data = $product->getData('media_gallery');
                Zebu_Auxiliary::info_variable($media_data);
                if (isset($media_data['images']))
                    Zebu_Auxiliary::info_variable($media_data['images']);*/
            }

        /**
         * Import options to the product given by sku
         * @param <string> $productSku
         * @param <array> $options array of options
         */
        public function import_options($productSku, $options){
                    // get the product model
            $product = Mage::getModel('catalog/product');
            $product->load($product->getIdBySku($productSku));

            /**
             * Remove existing custom options attached to the product
             *
             * @link http://magentodev.blogspot.com/2009/05/how-to-import-products-with-custo...
             */
            foreach ($product->getOptions() as $option) {
                $option->getValueInstance()->deleteValue($option->getId());
                $option->deletePrices($option->getId());
                $option->deleteTitles($option->getId());
                $option->delete();
            }

            // add the options to the product
            foreach($options as $option) {
                $option['product_id'] = $product->getId();
                //$option['sku'] = $productSku;

                foreach($option['values'] as $key => $value){
                    //$value['sku'] = $productSku.'_'.$value['sku'];
                    $option['values'][$key]['sku'] = $productSku.'_'.$value['sku'];
                    Zebu_Auxiliary::info_variable($option['values'][$key],2);
                }
                Zebu_Auxiliary::info_variable($option);
                $opt = Mage::getModel('catalog/product_option');
                $opt->setProduct($product);
                $opt->addOption($option);
                $opt->saveOptions();
            }
            $product->setHasOptions(1);
            $product->save();
    }

        function export_options($id){
        $optionArray = array();
            // load the product object
            $product = new Mage_Catalog_Model_Product();
            $product->load($id);

            // set the sku var to use as array key
            $productSku = $product->getSku();

            // get the product's options
            $options = $product->getOptions();

            foreach($options as $optionKey => $option) {
            Zebu_Auxiliary::info_variable($option->getData());
                // add the option data to the array
                $optionArray[$optionKey] = $option->getData();
                $optionArray[$optionKey]['values'] = array();

                // remove the option and product ids since these
                // can/will change on import
                unset($optionArray[$optionKey]['option_id']);
                unset($optionArray[$optionKey]['product_id']);

                // loop over any values for the option
                foreach($option->getValues() as $valueKey => $value) {
                    // add the value to the option
                    $optionArray[$optionKey]['values'][$valueKey] = $value->getData();

                    // remove ids from the array
                    unset($optionArray[$optionKey]['values'][$valueKey]['option_type_id']);
                    unset($optionArray[$optionKey]['values'][$valueKey]['option_id']);
                }
            }

        //$content = serialize($optionArray);

            return $optionArray;

         }

    public static function get_product_store($product) {
        foreach(Mage::app()->getStores() as $store) {
            if ($store->getRootCategoryId() == min($product->getCategoryIds()))
                return $store;
        }
    }

    public static function get_category_store($category) {
        $path = explode('/',$category->getPath());
        //print_r($path);
        if (count($path)<2)
            return null;
        foreach(Mage::app()->getStores() as $store) {
        //Zebu_Auxiliary::info_message($store->getId().' - '.$store->getRootCategoryId(),1);
            if ($store->getRootCategoryId() == $path[1])
                return $store;
        }
        return null;
    }

    protected static function get_connection_read() {
        if (!isset(self::$connection_read)) {
            $resource = Mage :: getSingleton( 'core/resource' );
            self::$connection_read = $resource->getConnection('core_read');
        }
        return self::$connection_read;
    }

    protected static function get_connection_write() {
        if (!isset(self::$connection_write)) {
            $resource = Mage :: getSingleton( 'core/resource' );
            self::$connection_write = $resource->getConnection('core_write');
        }
        return self::$connection_write;
    }

    /**
     * Get products count in category
     *
     * @param unknown_type $category
     * @return unknown
     */
    function get_product_count_by_category_id($catid) {
        $connection = self::get_connection_read();

        $productTable =Mage::getSingleton('core/resource')->getTableName('catalog/category_product');

        $select = $connection->select();
        $select->from(
            array('main_table'=>$productTable),
            array(new Zend_Db_Expr('COUNT(main_table.product_id)'))
            )
            ->where('main_table.category_id = ?', $catid)
            ->group('main_table.category_id');

        $counts = $connection->fetchOne($select);

        return intval($counts);
    }

    protected static $empty_categories_count;
    protected static $all_categories_count;
    public static $is_info = false;

    /**
     * Metoda rekurzivne prochazi cely strom kategorii a maze prazdne
     * (bez produktu a podkategorii) kategorie smerem od listu ke korenu.
     *
     * pozn.: metoda nevraci skutecny pocet produktu celeho podstromu. tak by tomu
     * bylo jen v pripade disjunktnich kategorii (kazdy prvek pouze v jedne kategorii),
     * takto jde jen o orientacni rozliseni prazdnych a neprazdnych kategorii.
     *
     * @param <type> $category
     * @return <type>
     */
    public static function delete_empty_category_branches($category, $delete_also_root_categories=false) {
        
        $subproducts_count = 0;
        if (self::$is_info) echo '<ul>';
        foreach ($category['children'] as $subcat) {
            if (self::$is_info) echo '<li>';
            $subproducts_count += self::delete_empty_category_branches($subcat,$delete_also_root_categories);
            if (self::$is_info) echo '</li>';
        }
        if (self::$is_info) echo '</ul>';

        $products_count = self::get_product_count_by_category_id($category['category_id']);
        if (self::$is_info) echo $category['name'].'('.$category['category_id'].') own products:'.$products_count;
        if (self::$is_info) echo ', children products:'.$subproducts_count;

//Zebu_Auxiliary::info_message('pred');
//$cat = Mage::getModel('catalog/category')->load($category['category_id']);
//Zebu_Auxiliary::info_message('nacteno');
       //echo 'hoho'.$cat->getId();

      // $cat->unsetData();
      // unset($cat);
 //      Zebu_Auxiliary::info_message('po unset');
                
        self::$all_categories_count++;
        //echo $category['level']."[$delete_also_root_categories]";
        if ($category['level']>1 || $delete_also_root_categories){
            if ($products_count+$subproducts_count==0) {
                if (self::$is_info) Zebu_Auxiliary::info_message('Delete '.$category['name'],2);
                
                $cat = Mage::getModel('catalog/category')->load($category['category_id']);
                $cat->delete();
                unset($cat);
                self::$empty_categories_count++;
            }else {
                if (self::$is_info) Zebu_Auxiliary::info_message('not empty '.$category['name']);
            }
        }else{
            if (self::$is_info) Zebu_Auxiliary::info_message('Root category '.$category['name'].' ... not deleted.');
        }

        return $products_count+$subproducts_count;
    }

    /**
     * Vymaze prazdne kategorie. Pokud neni zadano store, vymaze vsechny prazdne kategorie.
     *
     * @param <type> $store store objekt nebo kod
     */
    public static function delete_empty_categories($store = null, $delete_also_root_categories=false) {
        self::$empty_categories_count = 0;
        self::$all_categories_count = 0;
//Mage::app('admin', 'store');

        //$api = new Zebu_Mage_API();
        $api = new Mage_Catalog_Model_Category_Api();

        if (isset($store)){
            if (get_class($store)!='Mage_Core_Model_Store'){
                $store = Mage::app()->setCurrentStore($store)->getStore();
//                Mage::app()->setCurrentStore('admin');

    //                Mage::app('admin','store');//Mage :: app('default')-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
  //$userModel = Mage::getModel('admin/user');
  //$userModel->setUserId(0);
  //Mage::getSingleton('admin/session')->setUser($userModel);
            }
            $category = $api->tree(null, $store);
            //$api->call('category.tree', array( 'storeView' => $store->getId()));
        }
        else
            $category = $api->tree();
            //$api->call('category.tree', array());

        //Zebu_Auxiliary::info_variable($category);
        
        self::delete_empty_category_branches($category,$delete_also_root_categories);
        Zebu_Auxiliary::info_message('Deleted '.self::$empty_categories_count.' from '.self::$all_categories_count .' categories.');

    }

    public static function link_products_by_skus($product_sku, $linked_product_sku, $link_type_id){
        $product_id = Mage::getModel('catalog/product')->getIdBySku($product_sku);
        if (!$product_id)
            throw new Exception($product_sku.' - product not found.');

        $linked_product_id = Mage::getModel('catalog/product')->getIdBySku($linked_product_sku);
        if (!$linked_product_id)
            throw new Exception($linked_product_sku.' - product not found.');

        self::link_products(
            $product_id,
            $linked_product_id,
            $link_type_id
        );
    }

    public static function link_products($product_id, $linked_product_id, $link_type_id){

        $select = self::get_connection_read()
            ->select()
            ->from('catalog_product_link')
            ->where('product_id = ?', $product_id)
            ->where('linked_product_id = ?', $linked_product_id)
            ->where('link_type_id = ?', $link_type_id);

        $is_already_linked =  self::get_connection_read()->fetchOne($select);
        if ($is_already_linked)
            return;

        $query = sprintf('INSERT INTO `catalog_product_link` (`product_id`, `linked_product_id`, `link_type_id`) VALUES (%d, %d, %d)',$product_id, $linked_product_id, $link_type_id);

        self::get_connection_write()->query($query);
    }

    public static function remove_links_by_sku($product_sku, $linked_product_sku = null, $link_type_id = null){
        $product_id = Mage::getModel('catalog/product')->getIdBySku($product_sku);
        $linked_product_id = (isset($linked_product_id))
            ? Mage::getModel('catalog/product')->getIdBySku($linked_product_sku)
            : null;
        self::remove_links($product_id, $linked_product_id, $link_type_id);
    }

    public static function remove_links($product_id, $linked_product_id = null, $link_type_id = null){
        $condition = '`product_id`='.$product_id;
        if (isset($linked_product_id))
            $condition .= ' AND `linked_product_id`='.$linked_product_id;
        if (isset($link_type_id))
            $condition .= ' AND `link_type_id`='.$link_type_id;

        $query = 'DELETE FROM `catalog_product_link` WHERE '.$condition;
        
        self::get_connection_write()->query($query);
    }
}
?>
