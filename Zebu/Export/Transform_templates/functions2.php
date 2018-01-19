<?php

function getgoogleCategory($product)
{
    return 'Google Cat...';
}

function categoryMap($product)
{

    return 'Umění a zábava > Párty a oslavy > Dary > Řezané květiny';
}

function short($string, $limit)
{
    //$string = strip_tags(htmlspecialchars_decode(html_entity_decode($string)));
    //Zebu_Auxiliary::info_message($string);
    $string = strip_tags(html_entity_decode($string, ENT_QUOTES, "UTF-8"));
    //Zebu_Auxiliary::info_message($string,1);

    if (mb_strlen($string, 'UTF-8') <= $limit) return htmlspecialchars($string);
    $string = mb_substr($string, 0, $limit - 3, 'UTF-8') . '...';
    //Zebu_Auxiliary::info_message($string,2);
    return htmlspecialchars($string);
}

define('_URLBASE_', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
Zebu_Mage_ProductHelper::set_quoting(false);

$groupId = 0;//1
$websiteId = Mage::app()->getStore()->getWebsiteId();

$connection = Mage::getSingleton('core/resource')->getConnection('core_read');

$select = $connection
    ->select()
    ->from('catalog_product_index_price')
    ->where('customer_group_id = ' . (int)$groupId);

$prices = array();
foreach ($connection->query($select) as $priceRow) {

    if (isset($prices[$priceRow['website_id']][$priceRow['entity_id']])) {
        $prices[$priceRow['website_id']][$priceRow['entity_id']] = min($prices[$priceRow['website_id']][$priceRow['entity_id']], $priceRow['final_price']);
    } else
        $prices[$priceRow['website_id']][$priceRow['entity_id']] = $priceRow['final_price'];
}

$GLOBALS['prices'] = $prices;


function convertPrice($price, $ceil = true)
{
    if ($ceil)
        return ceil($price);
    else
        return $price;
}

function getProductPrice($product, $ceil = true)
{

    $prices = $GLOBALS['prices'];
    if (isset($prices[Mage::app()->getStore()->getWebsiteId()][$product->getId()])) {
            $price = $prices[Mage::app()->getStore()->getWebsiteId()][$product->getId()];
            $priceInclTax = Mage::helper('tax')->getPrice($product, $price, true,null, null, null, null, null, true);
            $priceInclTax = str_replace(',','.',$priceInclTax);
            return $priceInclTax;
    }

        $priceInclTax = Mage::helper('tax')->getPrice($product, $product->getPrice(), true,null, null, null, null, null, true);
        $priceInclTax = str_replace(',','.',$priceInclTax);
        return $priceInclTax;

}