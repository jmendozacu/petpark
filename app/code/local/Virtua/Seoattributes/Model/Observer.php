<?php

class Virtua_Seoattributes_Model_Observer
{

    /**
     * Remove all seo attributes connected to updated category
     * @param $observer
     */
    public function removeSeoattributesOnUpdateCategory($observer)
    {
        $event = $observer->getEvent();
        $category = $event->getCategory();
        if ($category->getId()) {
            $seoAttributesModel = Mage::getModel('virtua_seoattributes/seoattributes');
            $seoAttributesModel->getResource()->removeRowByCategoryId($category->getId());
        }
        return;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addNewCategories(Varien_Event_Observer $observer)
    {
        try {
            $storeId = $observer->getStore();
            $rewritesCollection = Mage::getModel('filterurls/url')
                ->getCollection()
                ->addFieldToFilter('store_id', $storeId);
            foreach ($this->_getExcludedAttributes() as $attr) {
                $rewritesCollection->addFieldToFilter('attributes', array('nlike' => '%'.$attr.'%'));
            }
            if (!$rewritesCollection) {
                return;
            }
            $collection = $observer->getCollection();
            $items = $collection->getItems();
            foreach ($rewritesCollection as $key => $rewrite) {
                $item = new Varien_Object;
                $item->setUrl($rewrite->getRequestPath());
                $items[] = $item;
            }
            $collection->setItems($items);
        } catch (Exception $exception) {
            Mage::log($exception->getMessage());
        }
    }

    protected function _getExcludedAttributes()
    {
        $helper = Mage::helper('virtua_seoattributes');
        $exludedAttributes = $helper->getExcludedAttributes();
        return $exludedAttributes;
    }
}
