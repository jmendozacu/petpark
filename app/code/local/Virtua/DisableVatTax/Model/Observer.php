<?php
/**
 * @category  DisableVatTax
 * @package   Virtua_DisableVatTax
 * @author    Maciej Skalny <contact@wearevirtua.com>
 * @copyright 2018 Copyright (c) Virtua (http://wwww.wearevirtua.com)
 */

/**
 * Class Virtua_DisableVatTax_Model_Observer
 */
class Virtua_DisableVatTax_Model_Observer extends Varien_Event_Observer
{
    /**
     * Disable tax for collect totals calculation if it is required
     */
    public function disableVatTaxForQuote(Varien_Event_Observer $observer)
    {
        /**
         * @var Virtua_DisableVatTax_Helper_Data $disableVatHelper
         */
        $disableVatHelper = Mage::helper('virtua_disablevattax');

        if ($disableVatHelper->shouldDisableVatTax()) {
            /**
             * @var Varien_Event $event
             */
            $event = $observer->getEvent();

            /**
             * @var Mage_Sales_Model_Quote $quote
             */
            $quote = $event->getQuote();
            $items = $quote->getAllVisibleItems();

            /**
             * @var Mage_Sales_Model_Quote_Item $item
             */
            foreach ($items as $item) {
                $product = $item->getProduct();
                $this->setZeroPercentTax($product);
            }
        }
    }

    /**
     * Clear shouldDisableVatTax attribute
     */
    public function clearShouldDisableVatTaxAttribute()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customer->setShouldDisableVatTax(0);
    }

    /**
     * Sets nonexistent tax class id.
     * @param $product
     */
    protected function setZeroPercentTax($product)
    {
        $product->setTaxClassId(-1);
    }
}
