<?php
class Zebu_Toptrans_Model_Transport_Package
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'toptrans';

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        if ($this->getConfigData('type') == 'O') { // per order
            $shippingPrice = $this->getConfigData('price');
        } elseif ($this->getConfigData('type') == 'I') { // per item
            $shippingPrice = $request->getPackageQty() * $this->getConfigData('price');

            if ($request->getAllItems()) {
                foreach ($request->getAllItems() as $item) {
                    if ($item->getFreeShipping() && !$item->getProduct()->getTypeInstance()->isVirtual()) {
                        $shippingPrice -= $item->getQty() * $this->getConfigData('price');
                    }
                }
            }
        } else {
            $shippingPrice = false;
        }



        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);

        if ($shippingPrice !== false) {
            $method = Mage::getModel('shipping/rate_result_method');

            $method->setCarrier('toptrans');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('toptrans');
            $method->setMethodTitle($this->getConfigData('name'));

            if ($request->getFreeShipping() === true) {
                $shippingPrice = '0.00';
            }

            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);

            $result->append($method);
        }

        return $result;
    }
    

    public function getAllowedMethods()
    {
        return array('toptrans'=>$this->getConfigData('name'));
    }
    
    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('http://www.toptrans.cz/itoptrans/new_zas_cis_obj_15_sk?xcis_obj=' . $tracking)
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));
        return $track;
    }      

}

