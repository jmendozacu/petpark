<?php

class Virtua_Heureka_Model_Observer
{
    public function updateHeurekaReviews()
    {
        try {
            $shopReviewUrl = 'http://www.heureka.sk/direct/dotaznik/export-review.php?key=6a04bb565412280cf32a78392b30aa4b';
            Zebu_HeurekaReviews::importEshopReviews($shopReviewUrl);
            $type = 'block_html';
            Mage::app()->getCacheInstance()->cleanType($type);
        } catch (Exception $exc) {
            Mage::log($exc->getMessage());
        }
    }

    public function updateHeurekaVariants()
    {
        $stores = Mage::app()->getStores(false, true);

        foreach($stores as $store){
            Mage::app()->setCurrentStore($store);
            $storeCode = $store->getCode();
            $file = $storeCode == 'default' ? './export/fb.xml' : './export/'.$storeCode.'_fb.xml';
            Zebu_Export_ExportCreator::export($file,
                'Zebu/Export/Transform_templates/google-cz.pxml',
                array('sku','name','ean','price','manufacturer','availability','description','url_path','image','category_ids'),
                'SHOP',
                array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                    'status'=>1,
                    'price'=>array('gt' => 0)),
                null,
                null,
                'rss2'
            );

            $export = './export/'.$storeCode.'_heureka-variants.xml';
            Zebu_Export_ExportCreator::export($export,
                'Zebu/Export/Transform_templates/heureka-variants.pxml',
                array('sku','ean','name','name_heureka','category_heureka','price','special_price','manufacturer','availability','description','url_path','image','category_ids','is_freeshipping'),
                'SHOP',
                array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                    'status'=>1,
                ),
                true);
        }
    }
}
