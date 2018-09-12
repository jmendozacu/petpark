<?php

class Virtua_UrlRewritesMap_Block_Download extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('urlrewritesmap');
        if (!$helper->getDownloadRewritesMapFileUrl()) {
            return 'File is not uploaded';
        }
        $this->setElement($element);
        $url = $helper->getDownloadRewritesMapFileUrl();

        Mage::log($url);

        return '<a href="' . $url . '" target="_blank" download>Download your current URL Rewrites file</a>';
    }
}