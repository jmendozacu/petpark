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
     * Disable tax for each product in quote if customer vat number is valid.
     */
    public function disableVatTaxForQuote(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        $isVatIdValid = $this->getCorrectVatNumberValidationResult();

        if ($isVatIdValid == 1) {
            $items = $quote->getAllVisibleItems();

            foreach ($items as $item) {
                $product = $item->getProduct();
                $product->setTaxClassId(-1);
            }
        }
    }

    /**
     * Checks is observer running on checkout page.
     *
     * @return bool
     */
    public function isItCheckoutPage()
    {
        return strpos(Mage::helper('core/url')->getCurrentUrl(), 'onepage') !== false;
    }

    /**
     * Get vat number validation results in dependence of location in shop.
     *
     * @return integer
     */
    public function getCorrectVatNumberValidationResult()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if ($this->isItCheckoutPage()) {
            $isVatIdValid = Mage::getSingleton('core/session')->getIsCheckoutVatIdValid();
        } else {
            $isVatIdValid = $customer->getIsVatIdValid();
        }

        return $isVatIdValid;
    }
}
