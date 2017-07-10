<?php

class Zebu_HeurekaReviews{
  
  protected static $table_name='zebu_heureka_reviews', $connection, $table_eshop_name='zebu_heureka_eshop_review';
  
  protected static function createTable(){
    $sql = 'CREATE TABLE IF NOT EXISTS `zebu_heureka_eshop_review` (
    `rating_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `unix_timestamp` timestamp NULL DEFAULT NULL,
    `total_rating` int(11) NOT NULL,
    `recommends` int(11) NOT NULL,
    `delivery_time` int(11) NOT NULL,
    `transport_quality` int(11) NOT NULL,
    `web_usability` int(11) NOT NULL,
    `communication` int(11) NOT NULL,
    `pros` text NOT NULL,
    `cons` text NOT NULL,
    `summary` text NOT NULL,
    PRIMARY KEY (`rating_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    
    self::getConnection()->query($sql);

/*ALTER TABLE `zebu_heureka_eshop_review`
 ADD PRIMARY KEY (`rating_id`);
  */
  }

  public static function getEshopReviews($count = 3){
     $connection = self::getConnection();
     $select = $connection
                ->select()
                ->from(self::$table_eshop_name)
                ->where("total_rating > 3")
                ->order('unix_timestamp desc')
                ->limit($count);
     //die($select);
     $data = $connection->fetchAll($select);
     return $data;
  }
  
  public static function saveEshopReview($review){
     $connection = self::getConnection();
     //echo get_class($connection);
     //Zend_Debug::dump(get_class_methods(get_class($connection)));
     $connection->insertOnDuplicate(self::$table_eshop_name, $review);//,array(')); 
     /*$select = $connection
                ->select()
                ->from(self::$table_name)
                ->where("`sku` LIKE '$sku'");
     $data = $connection->fetchAll($select);*/
  }  

  public static function importEshopReviews($url){
    self::createTable();

    $xml = simplexml_load_file($url);

    foreach($xml->review as $review){
          $data = array(
              'rating_id' => (string) $review->rating_id,
              'unix_timestamp' => date('Y-m-d H:i:s',(int)$review->unix_timestamp),
              'name' => (string) $review->name,
              'total_rating' => (int) $review->total_rating,
              'recommends' => (int) $review->recommends,
              'delivery_time' => (int) $review->delivery_time,
              'transport_quality' => (int) $review->transport_quality,
              'web_usability' => (int) $review->web_usability,
              'communication' => (int) $review->communication,
              
              'pros' => (string) $review->pros,
              'cons' => (string) $review->cons,
              'summary' => (string) $review->summary
          );
          var_dump($data);
          self::saveEshopReview($data);

    }
    echo "<hr/>Hotovo.";
  }

  
  public static function import($url){
    $xml = simplexml_load_file($url);
    $count = 0;
    $unknown = 0;
    $found = 0;
    foreach($xml->product as $product){
      $name = (string) $product->product_name;
      $prod = Mage::getModel('catalog/product')->loadByAttribute('name', $name);
      $count++;
      if (!$prod) {
          echo 'Neznam '.$name.'<br/>';$unknown++;
          continue;
      }
      $sku = $prod->getSku();
      foreach($product->reviews->review as $review){
          $data = array(
              'sku' => $sku,
              'rating_id' => (string) $review->rating_id,
              'timestamp' => (int) $review->unix_timestamp,
              'name' => (string) $review->name,
              'rating' => (float) $review->rating,
              'pros' => (string) $review->pros,
              'cons' => (string) $review->cons,
              'summary' => (string) $review->summary
          );
          self::saveReview($data);
      }
      $found++;
    }
    echo "<hr/>Nalezeno $found z $count, nenaslo se $unknown.";
  }
  
  public static function saveReview($review){
     $connection = self::getConnection();
     //echo get_class($connection);
     //Zend_Debug::dump(get_class_methods(get_class($connection)));
     $connection->insertOnDuplicate(self::$table_name, $review);//,array(')); 
     /*$select = $connection
                ->select()
                ->from(self::$table_name)
                ->where("`sku` LIKE '$sku'");
     $data = $connection->fetchAll($select);*/
  }
  
    protected static function getConnection() {
        if (!isset(self::$connection)) self::$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        return self::$connection;
    }  
  
  public static function getProductReviews($sku){
     $connection = self::getConnection();
     $select = $connection
                ->select()
                ->from(self::$table_name)
                ->where("`sku` = ?",$sku)
                ->order('timestamp desc')
                ->limit(10);
     //die($select);
     $data = $connection->fetchAll($select);
     return $data;
  }
  
} 