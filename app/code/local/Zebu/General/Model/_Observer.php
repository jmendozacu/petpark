<?php
class Zebu_General_Model_Observer{

            public function addGroupClass(Varien_Event_Observer $observer)
            {
                 $block = $observer->getBlock();
            //echo get_class($block)."--->" .get_class( $block->getParentBlock())."<br/>";
                if (get_class($block) == 'Mage_Page_Block_Html') {

                    $class = 'group-'.Mage::getSingleton('customer/session')->getCustomerGroupId(); 

                    $block->addBodyClass($class/*Mage::app()->getStore()->getCode()*/);

                    }
            }  
  
} 