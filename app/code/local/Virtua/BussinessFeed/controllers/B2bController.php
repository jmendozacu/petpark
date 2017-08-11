<?php

class Virtua_BussinessFeed_B2bController extends Mage_Core_Controller_Front_Action
{
    public function velkoobchodspecAction()
    {
        $model = Mage::getModel('bussinessfeed/feed');
        $feedFile = $model->getFeedFile();
        try {
            // if file not exists or file is old dated
            if ($model->fileIsOutDatedOrNotExists($feedFile)) {
                // get feed content and save it inside file
                $model->buildXmlFeed();
            }
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
