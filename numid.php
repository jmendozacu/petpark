<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '32M');
set_time_limit (0);
require_once'app/Mage.php';
Mage::app();

$addressesCollection = Mage::getResourceModel('customer/address_collection');
$addressesCollection->addAttributeToSelect('numid');
/* for particular address */
$addressesCollection->addFieldToFilter('vat_id',array('notnull' => true));

foreach ($addressesCollection as $address) {
  $address->setNumid($address->getVatId());
  $address->getResource()->saveAttribute($address,'numid');
  print_r($address->getData());
  //exit;
}
