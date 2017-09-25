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
}
