<?php
/**
 * Trida pro jednoduche nacitani csv souboru po radcich
 */
class Zebu_Tools_CSV_Reader{

    protected $csv_file;
    protected $delimiter;

    function __construct($csv_filename, $delimiter = Zebu_Tools_CSV_Helper::DELIMITER){
        $this->delimiter = $delimiter;
        $this->csv_file = fopen($csv_filename, 'r');
    }

    public function getRow(){
        $row = fgetcsv ($this->csv_file, 0, $this->delimiter);
        if (empty($row))
            return false;
        return $row;
    }

    public function close(){
        fclose($this->csv_file);
    }

}