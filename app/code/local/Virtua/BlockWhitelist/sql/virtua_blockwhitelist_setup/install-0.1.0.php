<?php
/**
 * Created by PhpStorm.
 * User: msyrek
 * Date: 05.12.2017
 * Time: 07:46
 */ 
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$blocklist = array(
    'filterproducts/featured_home_list',
    'filterproducts/latest_home_list',
    'filterproducts/newproduct_home_list',
    'filterproducts/sale_home_list',
    'filterproducts/mostviewed_home_list',
    'filterproducts/bestsellers_home_list',
    'blog/last',
    'newsletter/subscribe',
    'tag/popular',
    'zeon_manufacturer/home'
);

foreach($blocklist as $blockname)
{
    $whitelistBlock = Mage::getModel('admin/block')->load($blockname,'block_name');
    $whitelistBlock->setData('block_name', $blockname);
    $whitelistBlock->setData('is_allowed', 1);
    $whitelistBlock->save();
}



$installer->endSetup();