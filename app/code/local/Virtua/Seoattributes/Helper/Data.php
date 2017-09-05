<?php

class Virtua_Seoattributes_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_includedAttributes = array(
        'manufacturer', 'prevedenie_hracky', 'typ_korekcie_vycvik', 'vodotesnost', 'typ_oblecenia_hurtta',
        'urcenie_stekanie', 'typ_korekcie_stekanie', 'prevedenie_hurtta_oblecenie', 'prevedenie_gps',
        'prevedenie_oplotenia',
    );

    protected $_excludedAttributes = array(
        'availability', 'komplety', 'id', 'komplety_oplotenie', 'rozsiritelny_vycvik', 'komplety', 'velkost_filtrovatelna',
        'ajaxcatalog',
    );

    public function getIncludedAttributes()
    {
        return $this->_includedAttributes;
    }

    public function getExcludedAttributes()
    {
        return $this->_excludedAttributes;
    }

    /**
     * Return array with included attributes
     * @param $attributes
     */
    public function parseAttributes($attributes)
    {
        $out = array();
        $excludedAttributes = $this->getExcludedAttributes();
        if (!empty($attributes)) {
            foreach ($attributes as $attr => $id) {
                if (!in_array($attr, $excludedAttributes)) {
                    $out[$attr] = $id;
                }
            }
        }
        return $out;
    }

    public function replaceVariables($string, $variables)
    {
        return strtr($string, $variables);
    }
}
