<?php

class Zebu_Config_Block_System_Config_Source_LicenseKey extends Mage_Adminhtml_Block_System_Config_Form_Field
{
     /*public function toOptionArray(){
      //die('STOP OPTIONS');
      return array();
     }*/
     
    /*public function render(Varien_Data_Form_Element_Abstract $element)
    {
    //die('STOP RENDER');
    return 'NENENE'.parent::render($element);
    }*/
    
    /*protected function _getElementHtml($element){
      return 'HUHUHU';//.parent::getElementHtml($element);
    }*/

       protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('zebu_config');
        //Mage::log(count($element->getElements()));
        $modulname = preg_replace('~^zebu_config_zebu_modules_~','',$element->getId()); 
        $validityInfo = $helper->isLicenseValid($modulname)
          ? '<strong style="color:#76BF00">'.$helper->__('Key is VALID').'</strong>'
          : '<strong style="color:#DF0000">'.$helper->__('Key is INVALID').'</strong>';
        return $element->getElementHtml().$validityInfo;
    } 
}