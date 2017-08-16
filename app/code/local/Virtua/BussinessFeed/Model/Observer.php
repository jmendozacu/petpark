<?php

class Virtua_BussinessFeed_Model_Observer
{
    public function generateFeed()
    {
        $model = Mage::getModel('bussinessfeed/feed');
        $feeds = $model->getFeeds();
        if (empty($feeds)) {
            return;
        }
        try {
            foreach ($feeds as $feed) {
                $model->buildXmlFeed($feed['full_description']);
            }
        } catch(Exception $exc) {
            Mage::log($exc->getMessage());
        }
    }
}
