<?php
class Zebu_Intimecz_Model_Transport_Package
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'intimecz';

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

            $method->setCarrier('intimecz');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('intimecz');
            $method->setMethodTitle($this->getConfigData('name'));

            if ($request->getFreeShipping() === true) {
                $shippingPrice = '0.00';
            }
            
            if ((float)$shippingPrice>0 && (float)$this->getConfigData('freeshipping_from')){
                $cartTotal = 0;
                foreach ($request->getAllItems() as $item) {
                  $cartTotal += $item->getPriceInclTax()*$item->getQty();
                }

                if ($cartTotal >= (float)$this->getConfigData('freeshipping_from')){
                    $shippingPrice = '0.00';
                }

            }            
            
            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);

            $result->append($method);
            
            /*if ($this->getConfigData('cod_fee')){
                $method = Mage::getModel('shipping/rate_result_method');
    
                $method->setCarrier('intimecz');
                $method->setCarrierTitle($this->getConfigData('title'));
    
                $method->setMethod('intimecz_cod');
                $method->setMethodTitle($this->getConfigData('name') . ' + '.Mage::helper('core')->__('delivery fee'));
    
                if ($shippingPrice){
                    $shippingPrice += $this->getConfigData('cod_fee');
                }
                
                $method->setPrice($shippingPrice);
                $method->setCost($shippingPrice);
    
                $result->append($method);
            }*/
            
        }

        return $result;
    }
    

    public function getAllowedMethods()
    {
        return array('intimecz'=>$this->getConfigData('name'));
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('http://t-t.sps-sro.sk/result.php?cmd=VERKNR_SEARCH&sprache=SK&km_mandnr=002&kundenr=2668&verknr='.$tracking)
        //'http://www.toptrans.cz/itoptrans/new_zas_cis_obj_'.date('y').'_sk?xcis_obj=' . $tracking)
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));
        return $track;
    }

}

