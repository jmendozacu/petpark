<?php

class Virtua_DisableVatTax_Model_Observer extends Varien_Event_Observer
{
    /**
     * Observe sales_quote_collect_totals_before
     *
     * Disable tax if it is required
     *
     * Important! Don`t save this products.
     * We want change tax class only for collect totals calculation
     *
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

    /**
     * Observe:
     *  customer_login
     *  customer_logout
     *  customer_address_save_after
     *
     * Unset shouldDisableVatTax variable from session
     */
    public function removeShouldDisableVatTaxVariableFromSession()
    {
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        $customerSession->unsetData('shouldDisableVatTax');
    }
}