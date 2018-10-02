<?php

class Virtua_Dotdigitalgroup_Model_Email_Template extends Dotdigitalgroup_Email_Model_Email_Template
{
    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad()
    {
        //decompress the subject
        $this->setTemplateSubject(utf8_decode(utf8_encode($this->getTemplateSubject())));
        $templateText = $this->getTemplateText();
        $transactionalHelper = Mage::helper('ddg/transactional');
        //decompress the content body
        if ($transactionalHelper->isStringCompressed($templateText)) {
            $this->setTemplateText($transactionalHelper->decompresString($this->getTemplateText()));
        }

        return Mage_Core_Model_Email_Template::_afterLoad();
    }
}