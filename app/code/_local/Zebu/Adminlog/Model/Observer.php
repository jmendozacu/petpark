<?php

class Zebu_Adminlog_Model_Observer extends Mage_Admin_Model_Observer
{
    
    public function isEnabled(){
        return Mage::helper('adminlog')->isEnabled();
    }
    
    public function actionPreDispatchAdmin($event)
    {
        //$model = new Zebu_Adminlog_Model_Adminlog();

        if ($this->isEnabled()){
            $session  = Mage::getSingleton('admin/session');
            $request = Mage::app()->getRequest();
            $user = $session->getUser();
    
            if ($user){
              $model = Mage::getModel('adminlog/adminlog');//*/new Zebu_Adminlog_Model_Adminlog();
              $model->logAction($user->getId(),$request->getControllerName(),$request->getActionName(), $request->getParam('id',$request->getParam('user_id',$request->getParam('rid',null))));
            }
        }
        return parent::actionPreDispatchAdmin($event);

    }
    
    public function clearAdminlogs($schedule){
        if (!$this->isEnabled()){
          return;
        }
        
        Mage::log('clearAdminlogs...');
        $days = Mage::getStoreConfig('zebu_adminlog/zebu_adminlog_general/clean_after_days');
        if (empty($days)){
            Mage::log('empty days');
            return;
        }
        
        $model = new Zebu_Adminlog_Model_Adminlog();
        $model->clearLogs($days);

    }
}
