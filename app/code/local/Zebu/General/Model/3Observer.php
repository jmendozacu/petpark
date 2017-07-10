<?php
class Zebu_General_Model_Observer{

            public function addGroupClass(Varien_Event_Observer $observer)
            {
                 $block = $observer->getBlock();
            //echo get_class($block)."--->" .get_class( $block->getParentBlock())."<br/>";
                if (get_class($block) == 'Mage_Page_Block_Html') {

                    $class = 'group-'.Mage::getSingleton('customer/session')->getCustomerGroupId(); 

                    $block->addBodyClass($class/*Mage::app()->getStore()->getCode()*/);

                    }
            }  

            public function isEnabled(){
              return true;
            }

    public function updateAvailability($product, $save = true){
    /*
        374 'skladem'
        375 '3'
        376  7
        377  14
    */
    
    /*
    
$_resource = $this->getProduct()->getResource();
$optionValue = $_resource->getAttributeRawValue($_item, 'custom_attribute_value', Mage::app()->getStore());

    
    */  if (is_numeric($product)){
            $productId = $product; 
            $product = Mage::getModel('catalog/product')->load($productId);
        }
        if (empty($product)){
          Mage::log($productId.' - product not found');
        }
    
        $availabilityId = null;
    
        $attr = $product->getResource()->getAttribute("availability");
        $qty = $product->getStockItem()->getQty();
        if (
       //   ($product->getStockItem()->getManageStock() && !$product->getStockItem()->getIsInStock()) // vyprodano - nemelo by automaticky nastat, jedine rucnim nastavenim
        // ||//neni konf. a me mene ney 0ks a resi se sklad.
        //  (($qty<=0 && !$product->isConfigurable()) && $product->getStockItem()->getManageStock()/* && $product->getAvailability()==374*/)
          
          $product->getStockItem()->getManageStock() && 
          (
              !$product->getStockItem()->getIsInStock()
            || $qty<=0 && !$product->isConfigurable()
          )
          
          
         ){
          $availabilityId = $attr->getSource()->getOptionId($product->getAttributeText('availability_out'));
          
          //$product->addAttributeUpdate('availability', $availabilityId);
        }
        
        Mage::log($product->getSku().': 1 '.$availabilityId);
        
        if ( ($qty>0 || !$product->getStockItem()->getManageStock() || ($product->isConfigurable() && $product->getStockItem()->getIsInStock()) ) && $product->getAvailability()!=374){
          $availabilityId = 374;
          //$product->addAttributeUpdate('availability', $availabilityId);
        }
        
        Mage::log($product->getSku().': 1 '.$availabilityId);        
        
        if ($availabilityId && $save){
          $product->setAvailability($availabilityId);
          
          //echo $product->getAttributeText('availability');
          //die('ID: '.$availabilityId);
          
          Mage::log($product->getSku().': Change availability to '.$product->getAttributeText('availability'));
          Mage::getSingleton('catalog/product_action')->updateAttributes(
              array($product->getId()), //array with ids to be updated, 
              array('availability' => $availabilityId), //array with attributes to be updated, 
              0 //store id for the update : 0 = default values
          );
        }
        return $availabilityId;        
             
    }
    

    public function catalog_product_save_after($observer)
    {
        $product = $observer->getProduct();
        $this->updateAvailability($product);
    }
    
    

public function catalogInventorySave(Varien_Event_Observer $observer)
{
    if ($this->isEnabled()) {
        $event = $observer->getEvent();
        $item = $event->getItem();

        //if ((int)$_item->getData('qty') != (int)$_item->getOrigData('qty')) {
            
            $this->updateAvailability($item->getProductId());
            
            /*$params['product_id'] = $_item->getProductId();
            $params['qty'] = $_item->getQty();
            $params['qty_change'] = $_item->getQty() - $_item->getOrigData('qty');*/
        //}
    }
}

public function subtractQuoteInventory(Varien_Event_Observer $observer)
{
    if ($this->isEnabled()) {
        $quote = $observer->getEvent()->getQuote();
        foreach ($quote->getAllItems() as $item) {
              $this->updateAvailability($item->getProductId());
            /*$params = array();
            $params['product_id'] = $item->getProductId();
            $params['sku'] = $item->getSku();
            $params['qty'] = $item->getProduct()->getStockItem()->getQty();
            $params['qty_change'] = ($item->getTotalQty() * -1);*/
        }
    }
}

public function revertQuoteInventory(Varien_Event_Observer $observer)
{
    if ($this->isEnabled()) {
        $quote = $observer->getEvent()->getQuote();
        foreach ($quote->getAllItems() as $item) {
            $this->updateAvailability($item->getProductId());
/*            $params = array();
            $params['product_id'] = $item->getProductId();
            $params['sku'] = $item->getSku();
            $params['qty'] = $item->getProduct()->getStockItem()->getQty();
            $params['qty_change'] = ($item->getTotalQty());*/
        }
    }
}

public function cancelOrderItem(Varien_Event_Observer $observer)
{
    if ($this->isEnabled()) {
        
        $item = $observer->getEvent()->getItem();
        $this->updateAvailability($item->getProductId());
        
        /*$qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
        $params = array();
        $params['product_id'] = $item->getProductId();
        $params['sku'] = $item->getSku();
        $params['qty'] = $item->getProduct()->getStockItem()->getQty();
        $params['qty_change'] = $qty;*/
    }
}

public function refundOrderInventory(Varien_Event_Observer $observer)
{
    if ($this->isEnabled()) {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        foreach ($creditmemo->getAllItems() as $item) {
            $this->updateAvailability($item->getProductId());
            /*if ($item->getProduct()->getStockItem()->getQty() < 0.001){
              
            }*/
       }
    }
}

} 