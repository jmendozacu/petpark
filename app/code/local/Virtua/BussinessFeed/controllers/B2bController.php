<?php

class Virtua_BussinessFeed_B2bController extends Mage_Core_Controller_Front_Action
{
    public function velkoobchodspecAction()
    {
        $model = Mage::getModel('bussinessfeed/feed');
        $feedFile = $model->getFeedFile();
        try {
            // read feed xml
            $this->_readFeed($feedFile);
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }
        return;
    }

    public function feedAction()
    {
        $model = Mage::getModel('bussinessfeed/feed');
        $feedFile = $model->getFeedPath() . DS . 'cz' . DS . 'fulldesc_general_feed.xml';
        try {
            // read feed xml
            $this->_readFeed($feedFile);
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }
        return;
    }

    public function id2Action()
    {
        $model = Mage::getModel('bussinessfeed/feed');
        if (!is_null($this->getRequest()->getParam('fulldesc'))) {
            $feedFile = $model->getFeedPath() . DS . 'fulldesc_velkoobchod_feed.xml';
        } else {
            $feedFile = $model->getFeedPath() . DS . 'velkoobchod_feed.xml';
        }
        try {
            // read feed xml
            $this->_readFeed($feedFile);
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }
        return;
    }

    /**
     * Read feed xml
     * @param string $feedFile
     */
    protected function _readFeed($feedFile)
    {
        $this->getResponse()->clearHeaders()->setHeader(
            'Content-type',
            'text/xml'
        );
        $this->getResponse()->setBody(file_get_contents($feedFile));
        return;
    }
}
