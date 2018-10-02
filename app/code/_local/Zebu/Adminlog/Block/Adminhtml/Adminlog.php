<?php
require_once 'app/code/local/Zebu/Adminlog/ACL.php';

class Zebu_Adminlog_Block_Adminhtml_Adminlog extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_adminlog';
    $this->_blockGroup = 'adminlog';
    $this->_headerText = Mage::helper('adminlog')->__('Activity log');
    
    parent::__construct();

    $this->removeButton('add');
    
  }
}
