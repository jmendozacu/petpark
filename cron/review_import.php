<pre><?php
//header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit','512M');
chdir(dirname(__FILE__).'/..');

setlocale(LC_ALL, 'cs_CZ.UTF-8');
include_once 'app/Mage.php';
  Mage :: app()-> setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );
  $userModel = Mage::getModel('admin/user');
  $userModel->setUserId(0);
  Mage::getSingleton('admin/session')->setUser($userModel);

  $shopReviewUrl = 'http://www.heureka.sk/direct/dotaznik/export-review.php?key=6a04bb565412280cf32a78392b30aa4b';

  //$productReviewUrl = 'http://www.heureka.cz/direct/dotaznik/export-product-review.php?key=a8327b971dc93c0cd32a5a88bd21d472';
  
  Zebu_HeurekaReviews::importEshopReviews($shopReviewUrl);
  
  $type = 'block_html';
  Mage::app()->getCacheInstance()->cleanType($type);