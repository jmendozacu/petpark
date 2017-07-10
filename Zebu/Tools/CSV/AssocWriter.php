<?php
/**
 * Trida pro jednoduche ukladani do csv souboru s hlavickou, ktera je zadana pri
 * konstrukci. Vkladany radek je reprezentovan asociativnim polem, kde klice
 * odpovidaji hlavicce. Na poradi prvku v poli tedy nezalezi.
 */

class Zebu_Tools_CSV_AssocWriter extends Zebu_Tools_CSV_Writer{

    protected $header;
    protected $undefined;

    function __construct($csv_filename, $header = null, $delimiter = Zebu_Tools_CSV_Helper::DELIMITER){
        parent::__construct($csv_filename, $delimiter);
        $this->undefined = Zebu_Tools_CSV_Helper::UNDEFINED;
        $this->header = $header;
        if ($header)
        	parent::saveRow($header);
    }

    public function setUndefinedValue($undefined){
        $this->undefined = $undefined;
    }

    public function saveRow($assocRow){
        $row = array();
        if (!$this->header){
        	$this->header = array_keys($assocRow);
        	parent::saveRow($this->header);
        }
        foreach($this->header as $i => $key){
            if (!isset($assocRow[$key]))
                $assocRow[$key] = $this->undefined;
            $row[$i] = $assocRow[$key];
        }
        parent::saveRow($row);
    }
}