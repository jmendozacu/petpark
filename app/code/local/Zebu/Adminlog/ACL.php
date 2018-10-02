<?php
class ACL {
  public static function check($resource, $actions = array()) {
    if (empty($actions)) {
      return Mage::getSingleton('admin/session')->isAllowed($resource);
    } else {
      foreach ($actions as $action) {
        if (Mage::getSingleton('admin/session')->isAllowed($resource.'/'.$action))
          return true;
      }
    }
    return false;
  }
}