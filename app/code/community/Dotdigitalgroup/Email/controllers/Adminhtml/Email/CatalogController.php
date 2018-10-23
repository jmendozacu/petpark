<?php

class Dotdigitalgroup_Email_Adminhtml_Email_CatalogController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Main page.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->getLayout()->getBlock('head')->setTitle('Connector Catalog');
        $this->renderLayout();
    }

    /**
     * Check currently called action by permissions for current user.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'email_connector/reports/email_connector_catalog'
        );
    }
}
