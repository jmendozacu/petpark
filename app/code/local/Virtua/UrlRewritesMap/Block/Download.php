<?php

/**
 * Class Virtua_UrlRewritesMap_Block_Download
 */
class Virtua_UrlRewritesMap_Block_Download extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Virtua_UrlRewritesMap_Helper_Data $helper */
        $helper = Mage::helper('urlrewritesmap');
        if (!$helper->getDownloadRewritesMapFileUrl()) {
            return __('File is not uploaded');
        }
        $this->setElement($element);
        $url = $helper->getDownloadRewritesMapFileUrl();

        return '<a href="' . $url . '" target="_blank" download>' . __('Download your current URL Rewrites file') . '</a>';
    }
}