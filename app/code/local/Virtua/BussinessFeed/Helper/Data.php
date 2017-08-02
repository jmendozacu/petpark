<?php

class Virtua_BussinessFeed_Helper_Data extends Mage_Core_Helper_Abstract
{
    const PARAM_KEY = 'params';
    const PARAM_NAME = 'param_name';
    const PARAM_VAL = 'val';
    const SHOP_ITEM = 'shop_item';

    public function getXmlTop()
    {
        $out = '';
        $out .= '<?xml version="1.0" encoding="UTF-8" ?>';
        $out .= $this->buildTag('feed');
        return $out;
    }

    public function prepareXmlShopItem($data)
    {
        $out = '';
        if (empty($data)) {
            return $out;
        }
        foreach ($data as $value) {
            $out .= $this->buildTag(self::SHOP_ITEM);
            foreach ($value as $key => $val) {
                if (!$key) {
                    continue;
                }
                $val = $this->replaceChars($val);
                if ($key == self::PARAM_KEY) {
                    $out .= $this->prepareXmlParams($val);
                } else {
                    $out .= $this->prepareSingleRow($key, $val);
                }
            }
            $out .= $this->buildTag(self::SHOP_ITEM, true);
        }
        return $out;
    }

    public function prepareSingleRow($key, $value)
    {
        $out = '';
        if ($value === false) {
            $out .= $this->buildTag($key, true);
            return $out;
        }
        $out .= $this->buildTag($key);
        $out .= $value;
        $out .= $this->buildTag($key, true);
        return $out;
    }

    public function prepareXmlParams($params)
    {
        $out = '';
        if (empty($params)) {
            return $out;
        }
        foreach ($params as $name => $param) {
            $out .= $this->buildTag(self::PARAM_KEY);
            $out .= $this->buildTag(self::PARAM_NAME);
            $out .= $name;
            $out .= $this->buildTag(self::PARAM_NAME, true);
            $out .= $this->buildTag(self::PARAM_VAL);
            $out .= $param;
            $out .= $this->buildTag(self::PARAM_VAL, true);
            $out .= $this->buildTag(self::PARAM_KEY, true);
        }
        return $out;
    }

    public function getXmlBottom()
    {
        return $this->buildTag('feed', true);
    }

    public function buildTag($tag, $close = false)
    {
        return ($close) ? '</' . strtoupper($tag) . '>' : '<' . strtoupper($tag) . '>';
    }

    public function replaceChars($value)
    {
        $value = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $value);
        return $value;
    }

}