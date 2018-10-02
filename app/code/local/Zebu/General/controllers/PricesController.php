<?php

class Zebu_General_PricesController extends Mage_Adminhtml_Controller_Action //Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $productIds = (array)$this->getRequest()->getParam('product');
        $storeId    = (int)$this->getRequest()->getParam('store', 0);

        try {

            $resource = Mage::getModel('catalog/product')->getResource();       
                
            foreach($productIds as $id){
              
              if (!$resource->getAttributeRawValue($id, 'price_calculation_disabled', 0)){
                Mage::helper('zebu')->updateCzPrices($id);
              }          
            }

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.', count($productIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()
                ->addException($e, $this->__('An error occurred while updating the product(s) status.'));
        }

        $this->_redirect('adminhtml/catalog_product/', array('store'=> $storeId));
    }

}