<?php
require_once 'app/code/local/Zebu/Adminlog/ACL.php';

class Zebu_Adminlog_Adminhtml_AdminlogController extends Mage_Adminhtml_Controller_action {

  protected function _initAction() {
//    if (!ACL::check('config', array('crud','items'))) $this->_redirect('*');
    $this->loadLayout()
        ->_setActiveMenu('adminlog/items')
        ->_addBreadcrumb(Mage::helper('adminhtml')->__('Adminlog manager'), Mage::helper('adminhtml')->__('Adminlog manager'));

    return $this;
  }

  public function indexAction() {
    $this->_initAction()
        ->renderLayout();
  }

  public function exportCsvAction() {
    //if (!ACL::check('config', array('export'))) $this->_redirect('*/*/');
    $fileName   = 'adminlog.csv';
    $content    = $this->getLayout()->createBlock('adminlog/adminhtml_adminlog_grid')
        ->getCsv();

    $this->_sendUploadResponse($fileName, $content);
  }

  public function exportXmlAction() {
    //if (!ACL::check('config', array('export'))) $this->_redirect('*/*/');
    $fileName   = 'adminlog.xml';
    $content    = $this->getLayout()->createBlock('adminlog/adminhtml_adminlog_grid')
        ->getXml();

    $this->_sendUploadResponse($fileName, $content);
  }

  protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream') {
    $response = $this->getResponse();
    $response->setHeader('HTTP/1.1 200 OK','');
    $response->setHeader('Pragma', 'public', true);
    $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
    $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
    $response->setHeader('Last-Modified', date('r'));
    $response->setHeader('Accept-Ranges', 'bytes');
    $response->setHeader('Content-Length', strlen($content));
    $response->setHeader('Content-type', $contentType);
    $response->setBody($content);
    $response->sendResponse();
    die;
  }
}
