<?php

class Flagbit_FilterUrls_Controller_Router extends Mage_Core_Controller_Varien_Router_Standard
{
    /**
     * Helper function to register the current router at the front controller.
     *
     * @param Varien_Event_Observer $observer The event observer for the controller_front_init_routers event
     * @event controller_front_init_routers
     */
    public function addFilterUrlsRouter($observer)
    {
        Mage::log('ok');
        $front = $observer->getEvent()->getFront();

        $filterUrlsRouter = new Flagbit_FilterUrls_Controller_Router();
        $front->addRouter('filterurls', $filterUrlsRouter);
    }

    /**
     * Rewritten function of the standard controller. Tries to match the pathinfo on url parameters.
     *
     * @see Mage_Core_Controller_Varien_Router_Standard::match()
     * @param Zend_Controller_Request_Http $request The http request object that needs to be mapped on Action Controllers.
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            exit;
        }

        $identifier = trim($request->getPathInfo(), '/');

        // try to gather url parameters from parser.
        /* @var $parser Flagbit_FilterUrls_Model_Parser */
        $parser = Mage::getModel('filterurls/parser');
        $parsedRequestInfo = $parser->parseFilterInformationFromRequest($identifier, Mage::app()->getStore()->getId());

        if (!$parsedRequestInfo) {
            return false;
        }

        Mage::register('filterurls_active',true);

        // if successfully gained url parameters, use them and dispatch ActionController action
        $request->setRouteName('catalog')
            ->setModuleName('catalog')
            ->setControllerName('category')
            ->setActionName('view')
            ->setParam('id', $parsedRequestInfo['categoryId']);
        $pathInfo = 'catalog/category/view/id/' . $parsedRequestInfo['categoryId'];
        $requestUri = '/' . $pathInfo . '?';

        foreach ($parsedRequestInfo['additionalParams'] as $paramKey => $paramValue) {
            $requestUri .= $paramKey . '=' . $paramValue . '&';
        }

        $controllerClassName = $this->_validateControllerClassName('Mage_Catalog', 'category');
        $controllerInstance = Mage::getControllerInstance($controllerClassName, $request, $this->getFront()->getResponse());

        $request->setRequestUri(substr($requestUri, 0, -1));
        $request->setPathInfo($pathInfo);

        // dispatch action
        $request->setDispatched(true);
        $controllerInstance->dispatch('view');

        $request->setAlias(
            Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
            $identifier
        );

        if ($request->isXmlHttpRequest() && Mage::app()->getRequest()->getParam('ajaxcatalog')) {
            $viewpanel = Mage::app()->getLayout()->getBlock('catalog.leftnav')->toHtml();
            $productlist = Mage::app()->getLayout()->getBlock('category.products')->toHtml(); // Generate product list
            $response['status'] = 'SUCCESS';
            $response['viewpanel']=$viewpanel;
            $response['productlist'] = $productlist;
            $response['type'] = 'category';
            Mage::app()->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
            return;
        }

        return true;
    }
}