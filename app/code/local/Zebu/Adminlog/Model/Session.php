<?php

class Zebu_Adminlog_Model_Session extends Mage_Admin_Model_Session
{

    /**
     * Try to login user in admin
     *
     * @param  string $username
     * @param  string $password
     * @param  Mage_Core_Controller_Request_Http $request
     * @return Mage_Admin_Model_User|null
     */
    public function login($username, $password, $request = null)
    {
        if (empty($username) || empty($password)) {
            return;
        }

        try {
            /* @var $user Mage_Admin_Model_User */
            $user = Mage::getModel('admin/user');
            $user->login($username, $password);
            if ($user->getId()) {
                
                //Mage::getModel('adminlog/adminlog')->logAction($user->getId(),'index','login_success', $username);
		        $m = new Zebu_Adminlog_Model_Adminlog();
          	$m->logAction($user->getId(),'index','login_success', $username);

                $this->renewSession();

                if (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
                    Mage::getSingleton('adminhtml/url')->renewSecretUrls();
                }
                $this->setIsFirstPageAfterLogin(true);
                $this->setUser($user);
                $this->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
                if ($requestUri = $this->_getRequestUri($request)) {
                    Mage::dispatchEvent('admin_session_user_login_success', array('user' => $user));
                    header('Location: ' . $requestUri);
                    exit;
                }
            }
            else {
                //Mage::getModel('adminlog/adminlog')->logAction($user->getId(),'index','login_failed',$username);
                $m = new Zebu_Adminlog_Model_Adminlog();
                $m->logAction($user->getId(),'index','login_failed',$username);

                Mage::throwException(Mage::helper('adminhtml')->__('Invalid Username or Password.'));
            }
        }
        catch (Mage_Core_Exception $e) {
            Mage::dispatchEvent('admin_session_user_login_failed',
                    array('user_name' => $username, 'exception' => $e));
            if ($request && !$request->getParam('messageSent')) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $request->setParam('messageSent', true);
            }
        }

        return $user;
    }
}
