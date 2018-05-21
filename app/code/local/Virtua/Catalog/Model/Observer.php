<?php

class Virtua_Catalog_Model_Observer
{
    const CZ_STORE_CODE = 'cz';

    public function clearMenuCache(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getStores(false, true) as $storeCode => $store) {

            if ($storeCode === self::CZ_STORE_CODE && strpos(Mage::getBaseUrl(), 'onlydev.net') === false) {
                $file = '../../pet-park.cz/html/cat-' . $storeCode . '.cache';
            } else {
                $file = 'cat-' . $storeCode . '.cache';
            }

            if (file_exists($file)) {
                unlink($file);
                Mage::app()->getCacheInstance()->flush();
            }
        }
    }
}
