<?php

class Virtua_Dotdigitalgroup_Model_Adminhtml_Email_Template extends Dotdigitalgroup_Email_Model_Adminhtml_Email_Template
{
    /**
     * Decompress the text content for admin.
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad()
    {
        //decompress the title
        $this->setTemplateSubject(utf8_decode(utf8_encode($this->getTemplateSubject())));
        $templateText = $this->getTemplateText();
        $transactionalHelper = Mage::helper('ddg/transactional');
        if ($transactionalHelper->isStringCompressed($templateText)) {
            $this->setTemplateText($transactionalHelper->decompresString($templateText));
        }

        return Mage_Adminhtml_Model_Email_Template::_afterLoad();
    }
}