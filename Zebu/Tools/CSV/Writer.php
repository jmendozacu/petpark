<?php
/**
 * Trida pro jednoduche ukladani do csv souboru po radcich
 */
class Zebu_Tools_CSV_Writer{

    protected $csv_file;
    protected $delimiter;

    function __construct($csv_file_name_out, $delimiter = Zebu_Tools_CSV_Helper::DELIMITER){
        $this->delimiter = $delimiter;
        $this->csv_file = fopen($csv_file_name_out, 'w+');
    }

    public function saveRow($row){
        fputcsv($this->csv_file, $row, $this->delimiter);
    }

    public function close(){
        fclose($this->csv_file);
    }

}