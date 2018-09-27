<?php

class Virtua_DisableVatTax_Model_Tax_Calculation extends Mage_Tax_Model_Calculation
{
    /**
     * Get calculation tax rate by specific request
     *
     * @param  Varien_Object $request
     * @return float
     */
    public function getRate($request)
    {
        /** @var Virtua_DisableVatTax_Helper_Data $disableVatHelper */
        $disableVatHelper = Mage::helper('virtua_disablevattax');

        if ($disableVatHelper->shouldDisableVatTax()) {
            return 0;
        }

        if (!$request->getCountryId()
            || !$request->getCustomerClassId()
            || !$request->getProductClassId()
        ) {
            return 0;
        }

        $cacheKey = $this->_getRequestCacheKey($request);
        if (!isset($this->_rateCache[$cacheKey])) {
            $this->unsRateValue();
            $this->unsCalculationProcess();
            $this->unsEventModuleId();
            Mage::dispatchEvent(
                'tax_rate_data_fetch', array(
                'request' => $request)
            );
            if (!$this->hasRateValue()) {
                $rateInfo = $this->_getResource()->getRateInfo($request);
                $this->setCalculationProcess($rateInfo['process']);
                $this->setRateValue($rateInfo['value']);
            } else {
                $this->setCalculationProcess($this->_formCalculationProcess());
            }

            $this->_rateCache[$cacheKey] = $this->getRateValue();
            $this->_rateCalculationProcess[$cacheKey] = $this->getCalculationProcess();
        }

        return $this->_rateCache[$cacheKey];
    }
}