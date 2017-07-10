<?php

/**
 * A class for creating xml sheets for import into magento.
 * 
 * @author Ondřej Kohut
 *    
 */
class Zebu_Tools_XmlSheetCreator {
    /**
     * @var <array> array: attribute_name => attribute_type
     */
    private $attribute_set;

    /**
     * @var <array> array: attribute_name => default_value
     */
    private $attribute_default_values = array();

    /**
     * @var <pointer> file pointer if filename is set while construct
     */
    private $file;

    private $encoding;

    private $is_converting_special_chars_on = true;
    private $is_cdata_wrapping = false;
    
    //private $is_autoheader = false;
    private $is_autoheader_enabled = false;
    private $is_autodefinition_enabled = false;

    public function set_converting_special_chars($is_converting_special_chars_on = true){
        $this->is_converting_special_chars_on = $is_converting_special_chars_on;
    }

    public function set_cdata_wrapping($is_cdata_wrapping = true){
        $this->is_cdata_wrapping = $is_cdata_wrapping;
    }

    /**
     *
     * @param <string> $filename
     */
    public function __construct($filename=null, $is_autodefinition_enabled = false, $is_autoheader_enabled = false, $encoding = 'utf-8') {
        $this->is_autoheader_enabled = $is_autoheader_enabled;
        $this->is_autodefinition_enabled = $is_autodefinition_enabled;
        if (isset($filename)) $this->file = fopen($filename, "w+");
        $this->encoding = (preg_match('~utf~',$encoding))?'utf':$encoding;
        $this->write_head($encoding);
    }

    public function change_file($filename){
        $this->close();
        $this->file = fopen($filename, "w+");
        $this->write_head();
        $this->write_attribute_names_row();
    }
/*
    public function close_current_sheet_and_open_new_file($filename){
        $this->writeFoot();
        $this->close();
        $this->file = fopen($filename, "w+");
        $this->writeHead();
    }*/



    /**
     *
     * Set attributes by names.
     * All attribute types will be set to String.
     *
     * @param <array> $attributeNames Array of attribute names
     */
    public function set_attributes($attributeNames=array()) {

        $this->attribute_set = array();
        foreach ($attributeNames as $attributeName) {
            $this->attribute_set[$attributeName]='String';
        }
    }

    /**
     *
     * Set default values.
     *
     * @param <array> $attributeValues map: attribute name -> value
     */
    public function set_attribute_default_values($attributeValues=array()) {
        if (!isset($this->attribute_default_values)) $this->attribute_default_values = array();
        foreach ($attributeValues as $name=>$type) {
            $this->attribute_default_values[$name]=$type;
        }
    }

    /**
     *
     * Set types to given attributes
     *
     * @param <array> $attributeNames Array of attribute names
     */
    public function set_particular_attribute_types($attributeSet=array()) {
        if (!isset($this->attribute_set)) $this->attribute_set = array();
        foreach ($attributeSet as $name=>$type) {
            $this->attribute_set[$name]=$type;
        }
    }

    /**
     *
     * Set type to given attributes.
     * All attribute types will be set to String.
     *
     * @param <array> $attributeNames Array of attribute names
     */
    public function set_type_to_attributes($attributeNames=array(),$type) {
        if (!isset($this->attribute_set)) $this->attribute_set = array();
        foreach ($attributeNames as $name) {
            $this->attribute_set[$name]=$type;
        }
    }

    /**
     *
     * Set attributes by names.
     * All attribute types will be set to String.
     *
     * @param <array> $attributeSet array: attribute_name => attribute_type
     */
    public function set_attributes_with_types($attributeSet=array()) {
        $this->attribute_set = $attributeSet;
    }

    /**
     * Write the row with attribute names
     */
    public function write_attribute_names_row() {
        $this->write('<Row>');
        foreach ($this->attribute_set as $name => $type) {
            $this->write( '<Cell>'
                .'<Data ss:Type="String">'
                .$name
                .'</Data>'
                .'</Cell>');
        }
        $this->write('</Row>');
    }

    private function _write_row($attributes) {
        $this->write('<Row>');
        foreach ($attributes as $name=>$value) {
            $this->write( '<Cell>'
                .'<Data ss:Type="'.$this->attribute_set[$name].'">'
                .$this->_convert((string)$value)
                .'</Data>'
                .'</Cell>');
        }
        $this->write('</Row>');
    }

    public function write_row($attributes) {
        if ($this->is_autodefinition_enabled){ //definovat automaqticky podle prvniho radku
            $this->set_attributes(
                array_merge(
                    array_keys($attributes),
                    array_keys($this->attribute_default_values)
                )
            );
            $this->is_autodefinition_enabled = false; //jiz definovano
        }
        if ($this->is_autoheader_enabled){ //vypsat automaticky radek se jmeny atributu
            $this->write_attribute_names_row();
            $this->is_autoheader_enabled = false;
        }

        if (!isset($this->attribute_set)) echo 'WARNING: You need to declare column names before writing rows.';
        $attributesIndexed = array();

        foreach ($this->attribute_set as $name=>$type) {
            if (isset($attributes[$name]))
                $attributesIndexed[$name]=$attributes[$name];
            else if(isset($this->attribute_default_values[$name])){
                $attributesIndexed[$name]=$this->attribute_default_values[$name];
            } else $attributesIndexed[$name]='';
        }

        $this->_write_row($attributesIndexed);

    }

    /**
     * Write header of a xml sheet
     */
    private function write_head($encoding = 'utf-8') {
        $this->write( '<?xml version="1.0" encoding="'.$encoding.'"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet"><OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"/><ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"/><Worksheet ss:Name="sheet-import"><Table>');
    }

    /**
     * Write footer of a xml sheet
     */
    private function write_foot() {
        $this->write('</Table></Worksheet></Workbook>');
    }

    /**
     * Write to file or to output stream according to file definition
     * @param <string> $string Text to output
     */
    private function write($string) {
        if (isset($this->file)) fwrite($this->file, $string);
        else echo $string;
    }

    /**
     * Add xml sheet footer and close file if opened
     */
    public function close() {
        $this->write_foot();
        if (isset($this->file)) fclose($this->file);
        unset($this->file);
    }

    /*public function writeFootAndClose(){
        $this->writeFoot();
        $this->close();
    }*/

    private function _convert($string){
        $string = ($this->is_converting_special_chars_on) ? htmlspecialchars($string)
            : $string;


        //$string = Zebu_Tools_StringTools::iso2utf($string);//AutoCzech($string, $this->encoding);
        return ($this->is_cdata_wrapping) ? '<![CDATA['.$string.']]>'
            : $string;
    }
}
?>
