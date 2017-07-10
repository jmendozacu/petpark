<?php
    chdir(dirname(__FILE__).'/..');
    require_once 'app/Mage.php';
    umask( 0 );
    
    $store = $_GET['store'] ?: null;
    Mage::app($store); 
    
$file = 'export/google-'.$store.'.xml'; //export/rss.xml   

header('Content-Type: text/xml');
$xml = '<?xml version="1.0" encoding="utf-8"?>';
$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
      <title>Product export</title>
      <link>' . Mage::getBaseUrl() . $file.'</link>
      <description>Product export</description>';    

function short($string, $limit) {
    $string = strip_tags(html_entity_decode($string, ENT_QUOTES, "UTF-8"));
    if (mb_strlen($string, 'UTF-8') <= $limit)
        return htmlspecialchars($string);
    $string = mb_substr($string, 0, $limit - 3, 'UTF-8') . '...';
    return htmlspecialchars($string);
}

function getgoogleCategory($product) {
    return '<![CDATA[Animals & Pet Supplies > Pet Supplies]]>';
}

if (isset($_GET['c'])){    
  $customer = Mage::getModel('customer/customer')->load($_GET['c']);
  $session = Mage::getSingleton( 'customer/session' );
  $session->setCustomerAsLoggedIn(  $customer );
}

$_coreHelper = Mage::helper('core');
$_weeeHelper = Mage::helper('weee');
$_taxHelper = Mage::helper('tax');

foreach(Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('status',1)->addAttributeToFilter('visibility', 4) as $product){
//foreach(array(1) as $i){     $_product = $product = Mage::getModel('catalog/product')->load(1333);
     $product = Mage::getModel('catalog/product')->load($product->getId());
     
     $cats  = Zebu_Mage_ProductHelper::get_category_max_pathes_array(join(',', $product->getCategoryIds()), ' | ', 0);
     
    if($product->getTypeId() == "simple"):
        
    ob_start();
    ?>
    <item>
        <g:id><?php echo $product->getSku(); ?></g:id>
        <title><?php echo short($product->getName(), 70); ?></title>
        <description><?php echo short($product->getShortDescription(), 512); ?></description>
        <g:google_product_category><?php echo getGoogleCategory($product); ?></g:google_product_category>

        <?php foreach (Zebu_Mage_ProductHelper::get_category_max_pathes_array(join(',', $product->getCategoryIds()), ' | ', 0) as $cat) { ?>
            <g:product_type><?php echo htmlspecialchars($cat); ?></g:product_type>
            <?php break;
        } ?>

        <link><?php echo Mage::app()->getStore()->getBaseUrl() . $product->getUrlPath(); ?></link>
    <?php if (!preg_match('~^(no_selection)?$~', $product->getImage())) : ?> 
            <g:image_link><?php echo Mage::app()->getStore()->getBaseUrl() . 'media/catalog/product' . $product->getImage() ?></g:image_link>
        <?php endif; ?>
        <g:condition>new</g:condition>

        <?php if (Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty() > 0) : ?>
            <g:availability>in stock</g:availability>
    <?php else: ?>
            <g:availability>out of stock</g:availability>
        <?php endif; ?>
        <g:price><?php echo $_taxHelper->getPrice($product, $product->getFinalPrice(),true); ?></g:price>
        <g:brand><?php echo $product->getAttributeText('manufacturer'); ?></g:brand>
    <?php if (trim($product->getEan()) != "") { ?>
            <g:gtin><?php echo $product->getEan(); ?></g:gtin>
    <?php } ?>
        <g:mpn><?php echo $product->getSku(); ?></g:mpn>
    </item>
    <?php
    $xml .= ob_get_clean();
     
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
    foreach ($simple as $simple_product){
        $totalPrice = $basePrice;
        //loop through the configurable attributes
        foreach ($attributes as $attribute){
            //get the value for a specific attribute for a simple product
            $value = $simple_product->getData($attribute->getProductAttribute()->getAttributeCode());
            //add the price adjustment to the total price of the simple product
            if (isset($pricesByAttributeValues[$value])){
                $totalPrice += $pricesByAttributeValues[$value];
            }
        }
        //in $totalPrice you should have now the price of the simple product
        //do what you want/need with it
       
        ob_start();
            ?>
            <item>
                <g:id><?php echo $simple_product->getSku(); ?></g:id>
                <title><?php echo short($simple_product->getName(), 70); ?></title>
                <description><?php echo short($product->getShortDescription(), 512); ?></description>
                <g:google_product_category><?php echo getGoogleCategory($product); ?></g:google_product_category>

            <?php foreach ($cats as $cat) { ?>
                    <g:product_type><?php echo htmlspecialchars($cat); ?></g:product_type>
                    <?php break;
                } ?>

                <link><?php echo Mage::app()->getStore()->getBaseUrl() . $product->getUrlPath() . '#' . $simple_product->getId(); ?></link>
                <?php if (!preg_match('~^(no_selection)?$~', $product->getImage())) : ?> 
                    <g:image_link><?php echo Mage::app()->getStore()->getBaseUrl() . 'media/catalog/product' . $product->getImage() ?></g:image_link>
                <?php endif; ?>
                <g:condition>new</g:condition>

                <?php if (Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple_product)->getQty() > 0) : ?>
                    <g:availability>in stock</g:availability>
                <?php else: ?>
                    <g:availability>out of stock</g:availability>
                <?php endif; ?>
                <g:price><?php echo $_taxHelper->getPrice($product, $totalPrice, true); ?></g:price>
                <g:brand><?php echo $product->getAttributeText('manufacturer'); ?></g:brand>
            <?php if (trim($simple_product->getEan()) != "") { ?>
                    <g:gtin><?php echo $simple_product->getEan(); ?></g:gtin>
            <?php } ?>
                <g:mpn><?php echo $simple_product->getSku(); ?></g:mpn>
            </item>
            <?php
            $xml .= ob_get_clean();        
        
        }    
    endif;
    }

$xml .= '    </channel>
  </rss>';

file_put_contents($file, $xml);

echo $xml;    
