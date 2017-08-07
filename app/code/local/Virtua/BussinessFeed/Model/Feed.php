<?php

class Virtua_BussinessFeed_Model_Feed extends Mage_Core_Model_Abstract
{
    const GROUP_VELKOOBCHOD_SPEC_ID = 5;

    protected $params = array();

    protected $vat = '0.2000';

    public function buildXmlFeed()
    {
        $helper = Mage::helper('bussinessfeed');
        $out = '';
        $out .= $helper->getXmlTop();
        $out .= $helper->prepareXmlShopItem($this->prepareProductCollection());
        $out .= $helper->getXmlBottom();
        return $out;
    }

    public function getProductCollection()
    {
        $storeId = Mage::app()->getStore()->getStoreId();
        $products = Mage::getModel('catalog/product')->getCollection()
            //->addFieldToFilter('is_salable', '1')
            //->addFieldToFilter('is_in_stock', '1')
            //->addFieldToFilter('type_id', 'simple')
            ->setStore($storeId)
            ->setPageSize(1000)
            ->setCurPage(1);
        return $products;
    }

    public function getCustomerGroupIdByCode($groupCode)
    {
        $customerGroup = Mage::getModel('customer/group')->load($groupCode, 'customer_group_code');
        return $customerGroup->getEntityId();
    }

    public function getCustomerGroups()
    {
        $customerGroups = Mage::getModel('customer/group')->getCollection();
        return $customerGroups;
    }

    public function getParametersAssignedToConfigurableProduct($product)
    {
        $params = array();
        $configurableAttributesIds = array();
        $configurableParent = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        if (!empty($configurableParent)) {
            $parentProductId = $configurableParent[0];
            $configurableParent = $this->loadProduct($parentProductId);
            if ($configurableParent->isConfigurable()) {
                $attributes = $configurableParent->getTypeInstance()->getConfigurableAttributes($configurableParent);
                if (!empty($attributes)) {
                    foreach ($attributes as $attr) {
                        $configurableAttributesIds[] = $attr->getAttributeId();
                        foreach ($configurableAttributesIds as $attrId) {
                            $attribute = $this->getAttributeById($attrId);
                            if ($attribute) {
                                $attrCode = $attribute->getAttributeCode();
                                $attrKey = $product->getResource()->getAttribute($attrCode)->getStoreLabel();
                                $params[$attrKey] = $product->getAttributeText($attrCode);
                            }
                        }
                    }
                }
            }
        }
        return $params;
    }

    public function getProductGroupPrice($product, $groupId)
    {
        if (!is_null($product->getGroupPrice())) {
            $groupPrice = $product->getData('group_price');
            if (isset($groupPrice[$groupId]['price'])) {
                $customerGroupPrice = $groupPrice[$groupId]['price'];
                return $customerGroupPrice;
            }
        }
        $price = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
        if ($price) {
            return $price;
        }
        return $product->getPrice();
    }

    public function getParentDescription($product)
    {
        if ($product->getTypeId() == 'simple') {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                return htmlspecialchars($parent->getShortDescription());
            }
        }
        return htmlspecialchars($product->getShortDescription());
    }

    public function prepareProductCollection($customerGroup = self::GROUP_VELKOOBCHOD_SPEC_ID)
    {
        $baseMediaUrl = rtrim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA), '/');
        $preparedData = array();
        $products = $this->getProductCollection();
        foreach ($products as $key => $product) {
            $product = $this->loadProduct($product->getId());
            // get array of params
            $params = $this->getParametersAssignedToConfigurableProduct($product);
            // get group price of product
            $price = $this->getProductGroupPrice($product, $customerGroup);
            $preparedData[$key]['description'] =  $this->getParentDescription($product);
            $preparedData[$key]['imgurl'] = $baseMediaUrl . '/catalog/product' . $product->getImage();
            $preparedData[$key]['vat'] = $this->getVat();
            $preparedData[$key]['price'] = $price;
            $preparedData[$key]['price_vat'] = $this->getVatPrice($price);
            $preparedData[$key]['product'] = $product->getName();
            $preparedData[$key]['item_id'] = $product->getSku();
            $preparedData[$key]['params'] = $params;
//            $preparedData[$key]['itemgroup'] = $product->getDescription();
            $preparedData[$key]['categorytext'] = $this->getCategoryName($product);
            $preparedData[$key]['manufacturer'] = $product->getAttributeText('manufacturer');
            $preparedData[$key]['ean'] = $product->getEan();
            $preparedData[$key]['delivery_date'] = $product->getAttributeText('availability');
        }
        //die();
        //echo '<pre>'; print_r($preparedData); die();
        return $preparedData;
    }

    public function getVatPrice($price)
    {
        $vat = $this->getVat();
        $vatPrice = $price * $vat + $price;
        return number_format($vatPrice, 2);
    }

    public function getAttributeById($attributeId)
    {
        if (array_key_exists($attributeId, $this->getParams())) {
            return $this->getParams($attributeId);
        }
        $attribute = Mage::getModel('eav/entity_attribute')
            ->load($attributeId);
        if ($attribute) {
            return $this->addParam($attributeId, $attribute)->getParams($attributeId);
        }
        return;
    }

    public function getCategoryName($product)
    {
        $categoryIds = $product->getCategoryIds();

        if(count($categoryIds) ){
            $firstCategoryId = $categoryIds[0];
            $_category = Mage::getModel('catalog/category')->load($firstCategoryId);

            return $_category->getName();
        }
        return '';
    }

    public function loadProduct($id)
    {
        $product = Mage::getModel('catalog/product')->load($id);
        return $product;
    }

    public function addParam($key, $param)
    {
        $this->params[$key] = $param;
        return $this;
    }

    public function getParams($param = null)
    {
        if ($param) {
            return $this->params[$param];
        }
        return $this->params;
    }

    public function getVat()
    {
        return $this->vat;
    }

}
