<?php

class Virtua_Catalog_Model_Observer
{
    public function clearMenuCache(Varien_Event_Observer $observer)
    {
        if ($observer->getEvent()->getType() == 'catalog_category_prepare_save') {

            foreach (Mage::app()->getStores(false, true) as $storeCode => $store) {
                $file = 'cat-' . $storeCode . '.cache';
                $fileCz = '../../pet-park.cz/html/cat-' . $storeCode . '.cache';

                if (file_exists($file)) {
                    unlink($file);
                }

                if (file_exists($fileCz)) {
                    unlink($fileCz);
                }

            }
        }
    }
}
