<?php
require_once 'app/code/local/Zebu/Adminlog/ACL.php';

class Zebu_Adminlog_Block_Adminhtml_Adminlog_Grid extends Mage_Adminhtml_Block_Widget_Grid {
  public function __construct() {
    parent::__construct();
    $this->setId('adminlogGrid');
    $this->setDefaultSort('id');
    $this->setDefaultDir('DESC');
    $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection() {
    //new Zebu_Adminlog_Model_Mysql4_Adminlog();
    $collection = Mage::getModel('adminlog/adminlog')->getCollection();
    $this->setCollection($collection);
    return parent::_prepareCollection();
  }

  protected function _prepareColumns() {
    $this->addColumn('id', array(
        'header'    => Mage::helper('adminlog')->__('ID'),
        'align'     =>'right',
        'width'     => '30px',
        'index'     => 'id',
    ));
    //id 	user_id 	controller 	action 	subject 	http_user_agent 	server_addr 	remote_addr 	access_date
    $this->addColumn('user_id', array(
        'header'    => Mage::helper('adminlog')->__('User ID'),
        'align'     =>'left',
        'index'     => 'user_id',
        'width'     => '30px',
        'default'   => Mage::helper('adminlog')->__('n/a'),
    ));

    $this->addColumn('controller', array(
        'header'    => Mage::helper('adminlog')->__('Controller'),
        'align'     =>'left',
        'index'     => 'controller',
        'width'     => '50px',
    ));

    $this->addColumn('action', array(
        'header'    => Mage::helper('adminlog')->__('Action'),
        'align'     =>'left',
        'index'     => 'action',
        'width'     => '50px',
       // 'default'   => Mage::helper('adminlog')->__('n/a'),
    ));
    
    $this->addColumn('subject', array(
        'header'    => Mage::helper('adminlog')->__('Subject'),
        'align'     =>'left',
        'index'     => 'subject',
        'width'     => '30px',
       // 'default'   => Mage::helper('adminlog')->__('n/a'),
    ));
        
    $this->addColumn('http_user_agent', array(
        'header'    => Mage::helper('adminlog')->__('Http user agent'),
        'align'     =>'left',
        'index'     => 'http_user_agent',
    //  'width'     => '50px',
       // 'default'   => Mage::helper('adminlog')->__('n/a'),
    ));

    $this->addColumn('server_addr', array(
        'header'    => Mage::helper('adminlog')->__('Server address'),
        'align'     =>'left',
        'index'     => 'server_addr',
        'width'     => '30px',
        
       // 'default'   => Mage::helper('adminlog')->__('n/a'),
        'renderer'  => 'adminhtml/customer_online_grid_renderer_ip',        
    ));
    
    $this->addColumn('remote_addr', array(
        'header'    => Mage::helper('adminlog')->__('Remote address'),
        'align'     =>'left',
        'index'     => 'remote_addr',
        'width'     => '30px',

        //'default'   => Mage::helper('adminlog')->__('n/a'),
        'renderer'  => 'adminhtml/customer_online_grid_renderer_ip',        
    ));
    
    $this->addColumn('access_date', array(
        'header'    => Mage::helper('adminlog')->__('Access date'),
        'align'     =>'left',
        'index'     => 'access_date',
        'width'     => '30px',
        'type'      => 'datetime',
        //'default'   => Mage::helper('adminlog')->__('n/a'),
    ));
    
    $this->addExportType('*/*/exportCsv', Mage::helper('adminlog')->__('CSV'));
    $this->addExportType('*/*/exportXml', Mage::helper('adminlog')->__('XML'));
 
    return parent::_prepareColumns();
  }

}
