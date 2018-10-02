<?php
class Zebu_Adminlog_Block_Adminlog extends Mage_Core_Block_Template {
  public function _prepareLayout() {
    return parent::_prepareLayout();
  }

  public function getAdminlog() {
    if (!$this->hasData('adminlog')) {
      $this->setData('adminlog', Mage::registry('adminlog'));
    }
    return $this->getData('adminlog');
  }
}
