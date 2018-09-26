<?php

class Virtua_DisableVatTax_Model_Observer extends Varien_Event_Observer
{
    /**
     * @param Varien_Event_Observer $observer
    */
    public function disableVatTaxForQuote($observer)
    {
        /** @var Virtua_DisableVatTax_Helper_Data $disableVatHelper */
        $disableVatHelper = Mage::helper('virtua_disablevattax');

        if ($disableVatHelper->shouldDisableVatTax()) {
            /** @var Varien_Event $event */
            $event = $observer->getEvent();

            /** @var Mage_Sales_Model_Quote $quote */
            $quote = $event->getQuote();
            $items = $quote->getAllVisibleItems();

            /** @var Mage_Sales_Model_Quote_Item $item */
            foreach ($items as $item) {
                $product = $item->getProduct();
                $product->setTaxClassId(-1);
            }
        }
    }
}