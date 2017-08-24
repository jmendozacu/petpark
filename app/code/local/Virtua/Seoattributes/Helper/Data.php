<?php

class Virtua_Seoattributes_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_includedAttributes = array(
        'manufacturer', 'prevedenie_hracky', 'typ_korekcie_vycvik', 'vodotesnost', 'typ_oblecenia_hurtta',
        'urcenie_stekanie', 'typ_korekcie_stekanie', 'prevedenie_hurtta_oblecenie', 'prevedenie_gps',
        'prevedenie_oplotenia',
    );

    protected $_includedCategoriesId = array(
        152, 180, 106, 104, 160, 115, 172, 103,
    );

    public function getIncludedAttributes()
    {
        return $this->_includedAttributes;
    }

    public function getIncludedCategoriesId()
    {
        return $this->_includedCategoriesId;
    }

    /**
     * Return array with included attributes
     * @param $attributes
     */
    public function parseAttributes($attributes)
    {
        $out = array();
        $includedAttributes = $this->getIncludedAttributes();
        if (!empty($attributes)) {
            foreach ($attributes as $attr => $id) {
                if (in_array($attr, $includedAttributes)) {
                    $out[$attr] = $id;
                }
            }
        }
        return $out;
    }

    /**
     * Return category id if given category is included
     * @param $categoryId
     * @return int
     */
    public function categoryIsIncluded($categoryId)
    {
        if (!$categoryId) {
            return 0;
        }
        $includedCategoriesId = $this->getIncludedCategoriesId();
        return ($categoryId && in_array($categoryId, $includedCategoriesId)) ? $categoryId : 0;
    }

    public function replaceVariables($string, $variables)
    {
        return strtr($string, $variables);
    }
}
