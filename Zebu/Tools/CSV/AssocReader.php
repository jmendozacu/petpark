<?php 
/**
 * Trida pro jednoduche nacitani csv souboru s definovanou hlavickou. Pri konstrukci
 * je hlavicka automaticky nactena. Radky se pak vraceji jako asociativni pole,
 * jejichz klici jsou polozky hlavicky.
 */
class Zebu_Tools_CSV_AssocReader extends Zebu_Tools_CSV_Reader{

    protected $header;

    function __construct($csv_filename, $delimiter = Zebu_Tools_CSV_Helper::DELIMITER){
        parent::__construct($csv_filename, $delimiter);
        $this->header = parent::getRow();
    }

    public function getRow(){
        $row = parent::getRow();
        if (!$row)
            return false;
        $assocRow = array();
        foreach($this->header as $i => $key){
            if (!isset($row[$i]))
                $row[$i] = Zebu_Tools_CSV_Helper::UNDEFINED;
            $assocRow[$key] = $row[$i];
        }
        return $assocRow;
    }

}
