<?php

class Virtua_BussinessFeed_B2bController extends Mage_Core_Controller_Front_Action
{
    public function velkoobchodspecAction()
    {
        $model = Mage::getModel('bussinessfeed/feed');
        header("Content-type: text/xml");
        echo $model->buildXmlFeed();
    }
}
