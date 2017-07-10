<?php
/**
 * Trida pro transformace magentem exportovaneho xml do xml definovaneho sablonou (zbozi.cz, hyperzbozi atd.)
 * Sablony jsou ve formatu xml se specialnimi tagy.
 *  
 * @author Ondřej Kohut
 */
class Zebu_Export_ExportCreator {

    protected static $encoding_in;
    protected static $encoding_out;
    protected static $optional_elements = array();
    /**
     * Evaluate node value
     * @param <string> $text
     * @param <array>  $product
     * @return <string>
     */
    protected static function eval_node_value($text, &$product) {
        $res = trim($text);
        //Zebu_Auxiliary::info_message($res);
        $res = /*empty($res)*/($res == '') ? "''" : $res;
        //Zebu_Auxiliary::info_message(print_r($res,true));
        $value = 'init';
        $expr = '$value = '.$res.';';
        
        //Zebu_Auxiliary::info_message($text.' - '.$value);
        eval($expr);
        //Zebu_Auxiliary::info_message('val: '.print_r($value,true));
        //Zebu_Auxiliary::info_message($text.' - '.$value);
        //$value = iconv(self::$encoding_in, self::$encoding_out, $value);
        
        if (!is_array($value))
            return htmlspecialchars($value);

        foreach($value as $i => $item){
            $value[$i] = htmlspecialchars($item);
        }
        return $value;
    }


    /**
     * Recursive function to evaluate leaves' values.
     *
     * @param <DomNode> $node
     * @param <array> $product
     */
    protected static function eval_node_tree(&$node, &$product) {
    //  if ($node->nodeName=='#text') return;
       /* //<___EXPORTER_LIST___>
        if ($node->nodeName == self::EXPORTER_LIST){
            if($node->hasChildNodes()){
                $child = $node->childNodes[0];
                $values = $node->nodeValue = self::eval_node_value($node->nodeValue, $product);
                
                $parent = $node->parentNode;
                foreach($values as $value){
                    $new_child = $node->cloneNode();
                    $new_child->nodeValue = $value;
                    $parent->insertBefore($new_child, $node);
                }
                $parent->removeChild($node);
                return;
                //$child = $parent->insertBefore($child_nodes->item($j)->cloneNode(true), $template_node);
            }
        }*/

        if($node->hasChildNodes()) {
            $children = $node->childNodes;
            $subelement_exists = false;
            //for($i=0;$i<$children->length;$i++) {
           /* foreach($children as $child) {
                //$child = $children->item($i);
                //if ($child->nodeName=='#text') continue;
                if ($child->nodeName!='#text') {
                    self::eval_node_tree($child, $product);
                    $subelement_exists = true;
                }
            }*/
            $i=0;
            while($i<$children->length){
                //Zebu_Auxiliary::info_message($i.'/'.$children->length);
                $child = $children->item($i);
                if ($child->nodeName!='#text') {
                    $i += self::eval_node_tree($child, $product);
                    $subelement_exists = true;
                } else $i++;
            }

            if (!$subelement_exists) { //je list, tedy vyhodnotit
            //echo self::eval_node_value($node->nodeValue);//echo $node->nodeName.'['.$node->nodeValue.']<br/>';
                $value = self::eval_node_value($node->nodeValue, $product);
                if (!is_array($value)){
                    $node->nodeValue = $value;
                    //Zebu_Auxiliary::info_message($value.', '.$node->nodeName, 2);
                    if (''==trim($value) && self::is_optional($node->nodeName)){
                        //Zebu_Auxiliary::info_message($node->nodeName.' prazdny a nepovinny', 2);
                        $parent = $node->parentNode;
                        $parent->removeChild($node);
                        return 0; //odebran jeden element
                    }
                    return 1;
                }
                else{
                     $parent = $node->parentNode;
                     foreach($value as $subvalue){
                         $new_child = $node->cloneNode();
                         $new_child->nodeValue = $subvalue;
                         $parent->insertBefore($new_child, $node);
                     }
                     $parent->removeChild($node);
                     return count($value); //pridany elementy
                }
            }

        }
        return 1;
    }

    protected static function is_optional($element_name){
        //Zebu_Auxiliary::info_message($element_name.'...'.join(',',self::$optional_elements));
        return in_array($element_name, self::$optional_elements);
    }

    public static function transform_exports($input_xml_sheet_file_names, $xml_teplate_file_name, $output_xml_file_name) {

        $template = new DomDocument();
        $template->load($xml_teplate_file_name);

        self::$encoding_in = (isset($input_xml->encoding)) ? $input_xml->encoding : 'utf-8';
        self::$encoding_out = (isset($template->encoding)) ? $template->encoding : 'utf-8';

        //echo $input_xml->encoding.'; '.$template->encoding;
        //echo self::$encoding_in.'; '.self::$encoding_out;

        //nacte elementy ___EXPORTER_CODE___ a provede jejich kod
        $template_nodes = $template->getElementsByTagName('___EXPORTER_CODE___');
        $template_nodes_array = array();
        for ($i = 0 ; $i < $template_nodes->length ; $i++) {
            $template_node = $template_nodes->item($i);
            $template_nodes_array[] = $template_node;
            eval($template_node->nodeValue);
        }

        //odstrani elementy ___EXPORTER_CODE___ z vysledneho xml
        foreach ($template_nodes_array as $node) {
            $parent = $node->parentNode;
            $parent->removeChild($node);
        }

        //nacte sablony pro produkty
        $template_nodes = $template->getElementsByTagName('___EXPORTER_PRODUCT_TEMPLATE___');
        $template_nodes_array = array();
        for ($i = 0 ; $i < $template_nodes->length ; $i++) {
            $template_node = $template_nodes->item($i);
            $template_nodes_array[] = $template_node;

            if ($template_node->hasAttribute('optional')){
                    self::$optional_elements = explode(',', $template_node->getAttribute('optional'));
                    //Zebu_Auxiliary::info_variable(self::$optional_elements);
            }else self::$optional_elements = array();
            
            $parent = $template_node->parentNode;


            //postupne zpracovava z magenta exportovane xml s produkty
            foreach ($input_xml_sheet_file_names as $input_xml_sheet_file_name) {
                Zebu_Auxiliary::info_message('transform '.$input_xml_sheet_file_name);
                $input_xml = simplexml_load_file($input_xml_sheet_file_name);
                //$input_xml = new SimpleXMLElement($input_xml_sheet_file_name,~LIBXML_NOBLANKS, true);
                //LIBXML_NOBLANKS

                $is_head_loaded = false;
                foreach ($input_xml->Worksheet->Table->Row as $row) {
                    if (!$is_head_loaded) { //nacteni nazvu atributu
                        $header = array();
                        foreach ($row->Cell as $cell) {
                            $header[] = (string) $cell->Data;
                        }
                        $is_head_loaded = true;
                        continue;
                    }
                    $product = array();
                    $product['s_store_entity'] = 'saty';
                    $product['s_code'] = '';
                    $ii=0;
                    foreach ($row->Cell as $cell) { //nacteni hodnot atributu
                        //Zebu_Auxiliary::info_message($header[$ii].': '.$cell->Data,1);
                        $product[(string)$header[$ii]] = (string)$cell->Data;
                        $ii++;
                    }

                    $child_nodes = $template_node->childNodes;

                    for ($j = 0 ; $j < $child_nodes->length ; $j++) {
                    //$child = $parent->appendChild($child_nodes->item($j)->cloneNode(true));
                        //pro kazdy produkt prida sablonu elementu
                        $child = $parent->insertBefore($child_nodes->item($j)->cloneNode(true), $template_node);
                        //vyhodnoti pridany element - doplni sablonu hodnotami
                        self::eval_node_tree($child,$product);
                    }

                }
            }

        }

        foreach ($template_nodes_array as $node) {
            $parent = $node->parentNode;
            $parent->removeChild($node);
        }

        $template->encoding = self::$encoding_out;
        $template->save($output_xml_file_name);
    }


    public static function transform_export($input_xml_sheet_file_name, $xml_teplate_file_name, $output_xml_file_name) {

        return self::transform_exports(array($input_xml_sheet_file_name), $xml_teplate_file_name, $output_xml_file_name);

    }
    
    public static function export($export_file_name, $template, $fields, $root_element_name = 'SHOP', $filters = array(), $batch_number=null, $limit=null, $type = null){
        if($type == 'rss2') {
            file_put_contents($export_file_name, 
'<?xml version="1.0" encoding="utf-8"?>
  <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
      <title>Product export</title>
      <link>' . Mage::getBaseUrl() . ltrim($export_file_name , './') . '</link>
      <description>Product export</description>'
      );

            self::batch_export($export_file_name, $template, $fields, $filters, $batch_number, $limit);

            file_put_contents($export_file_name, '
    </channel>
  </rss>', FILE_APPEND);

        }else{
       file_put_contents($export_file_name, '<?xml version="1.0" encoding="utf-8"?'.'>
<'.$root_element_name.'>');
       self::batch_export($export_file_name, $template, $fields, $filters, $batch_number, $limit);
       file_put_contents($export_file_name, '
</'.$root_element_name.'>', FILE_APPEND);

        }


    }
    
    public static function batch_export($export_file_name, $template, $fields, $filters = array(), $batch_number=null, $limit=null){
        //Zebu_Auxiliary::start_timer();
	//Mage::unregister('_resource_singleton/catalog/product_flat')‌​;

        //$products = Mage::getResourceModel('catalog/product_collection');
//	$products = Mage::getModel('catalog/product')->getCollection();

//vypnu FLAT
$process = Mage::helper('catalog/product_flat')->getProcess();
$status = $process->getStatus();
$process->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
/** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
$products  = Mage::getResourceModel('catalog/product_collection'); // Use EAV tables
// ... custom stuff
//$process->setStatus($status);


        //$websiteId = Mage::app()->getStore()->getWebsiteId();
        //$products->addWebsiteFilter($websiteId);
        foreach($filters as $field => $condition){
            $products->addAttributeToFilter($field, $condition);
        }
        $products->addAttributeToSelect($fields);
        if ($batch_number && $limit)
            $products->setPage($batch_number, $limit);
        
            //$_productCollection->addAttributeToFilter('prefer',get_option_id('novinka'));
            //$_productCollection->getSelect()->order('rand()');
            //$_productCollection->addStoreFilter();
            //$numProducts = $this->getNumProducts() ? $this->getNumProducts() : $count_limit;
            //$_productCollection->setPage(1, $numProducts);
        echo 'SEL: '.$products->getSelect().'<br/>';
        foreach($products as $product){
          ob_start();
          include $template;
          $content = ob_get_clean();
          file_put_contents($export_file_name, $content, FILE_APPEND);
        }

	$process->setStatus($status);

        //Zebu_Auxiliary::info_message($products->count().' products exported [batch number '.$batch_number.'] ... '.Zebu_Auxiliary::stop_timer().'s');
    }
    
}

//Zebu_Export_ExportCreator::transform_export('export_all_products.xml', 'snakup_zbozi_cz.xml', 'snakup_export.xml');

