<?php
/**
 * Pomocna trida pro dulezite konstanty a pomocne funkce pro praci s csv soubory.
 */
class Zebu_Tools_CSV_Helper{
    const UNDEFINED = '';//'#undef#';
    const DELIMITER   = ';';

    public static function isUndefined($value){
        return self::UNDEFINED == $value;
    }
}