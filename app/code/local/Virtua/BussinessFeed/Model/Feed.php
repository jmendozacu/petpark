<?php

class Virtua_BussinessFeed_Model_Feed extends Mage_Core_Model_Abstract
{
    const GROUP_GENERAL = 1;
    const GROUP_VELKOOBCHOD_ID = 2;
    const GROUP_VELKOOBCHOD_SPEC_ID = 5;

    protected $params = array();

    protected $vat = '0.2000';

    protected $feedFile = 'velkoobchod_spec_feed.xml';
    protected $storeVersion;
    protected $storeVersionId;
    protected $includePrices;
    protected $groupId;
    protected $extraPrice;
    protected $fullDescription = false;

    protected $feeds = array(
        array(
            'group_id' => self::GROUP_VELKOOBCHOD_SPEC_ID,
            'full_description' => false,
            'store' => 'sk',
            'filename' => 'velkoobchod_spec_feed.xml',
            'include_prices' => true,
            'extra_price' => false,
        ),
        array(
            'group_id' => self::GROUP_VELKOOBCHOD_SPEC_ID,
            'full_description' => true,
            'store' => 'sk',
            'filename' => 'velkoobchod_spec_feed.xml',
            'include_prices' => true,
            'extra_price' => false,
        ),
        array(
            'group_id' => self::GROUP_VELKOOBCHOD_ID,
            'full_description' => false,
            'store' => 'sk',
            'filename' => 'velkoobchod_feed.xml',
            'include_prices' => true,
            'extra_price' => self::GROUP_GENERAL,
        ),
        array(
            'group_id' => self::GROUP_VELKOOBCHOD_ID,
            'full_description' => true,
            'store' => 'sk',
            'filename' => 'velkoobchod_feed.xml',
            'include_prices' => true,
            'extra_price' => self::GROUP_GENERAL,
        ),
        array(
            'group_id' => self::GROUP_GENERAL,
            'full_description' => true,
            'store' => 'cz',
            'filename' => 'general_feed.xml',
            'include_prices' => false,
            'extra_price' => false,
        ),
    );

    public function getFeeds()
    {
        return $this->feeds;
    }

    public function fileIsOutDatedOrNotExists($file)
    {
        return (!file_exists($file) || filemtime($file) < time() - 60 * 60 * 4);
    }

    /**
     * Feed's root path
     * @return string
     */
    public function getFeedPath()
    {
        $feedPath = Mage::getBaseDir() . DS . 'export' . DS . 'feed';
        if ($this->storeVersion == 'cz') {
            $feedPath .= DS . 'cz';
        }
        return $feedPath;
    }

    /**
     * Feed's file absolute path
     * @return strings
     */
    public function getFeedFile()
    {
        $showFullDescription = Mage::app()->getRequest()->getParam('fulldesc');
        if ((isset($showFullDescription) && !is_null($showFullDescription)) || $this->fullDescription) {
            $feedFile = $this->getFeedPath() . DS . 'fulldesc_' .$this->feedFile;
        } else {
            $feedFile = $this->getFeedPath() . DS . $this->feedFile;
        }
        return $feedFile;
    }

    /**
     * Saving data into the file
     * @param $data
     * @return bool|int
     */
    public function appendData($data) {
        $file = $this->getFeedFile();
        if ($this->fileIsOutDatedOrNotExists($file)) {
            //override
            return file_put_contents($file, $data);
        } else {
            //append
            return file_put_contents($file, $data, FILE_APPEND);
        }
    }

    protected function _getOption($option)
    {
        return (isset($option)) ? $option : null;
    }

    /**
     * Builds xml feed
     */
    public function buildXmlFeed($feedOptions)
    {
        $this->groupId = $this->_getOption($feedOptions['group_id']);
        $this->includePrices = $this->_getOption($feedOptions['include_prices']);
        $this->storeVersion = $this->_getOption($feedOptions['store']);
        $this->storeVersionId = ($this->storeVersion == 'cz') ? 2 : 1;
        $this->feedFile = $this->_getOption($feedOptions['filename']);
        $this->extraPrice = $this->_getOption($feedOptions['extra_price']);
        $this->fullDescription = $this->_getOption($feedOptions['full_description']);
        if (!is_dir($this->getFeedPath())) {
            mkdir($this->getFeedPath());
        }
        $helper = Mage::helper('bussinessfeed');
        $this->appendData($helper->getXmlTop());
        $this->prepareProductCollection();
        $this->appendData($helper->getXmlBottom());
    }

    /**
     * Retrieves product collection
     * @return mixed
     */
    public function getProductCollection()
    {
        //$storeId = Mage::app()->getStore()->getStoreId();
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('type_id', array('neq' =>'configurable'))
            ->setStore($this->storeVersionId)
            ->getAllIdsCache();
        return $products;
    }

    /**
     * Retrieves customer group id
     * @param $groupCode
     * @return mixed
     */
    public function getCustomerGroupIdByCode($groupCode)
    {
        $customerGroup = Mage::getModel('customer/group')->load($groupCode, 'customer_group_code');
        return $customerGroup->getEntityId();
    }

    /**
     * Retrieves customer groups collection
     * @return object
     */
    public function getCustomerGroups()
    {
        $customerGroups = Mage::getModel('customer/group')->getCollection();
        return $customerGroups;
    }

    /**
     * Retrieves product's parameters which is set as super attributes - they adjust product's price
     * Example: product's size, color
     * @param $product
     * @return array
     */
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
                                $params[$attrKey]['key'] = $product->getAttributeText($attrCode);
                                $params[$attrKey]['id'] = $product->getResource()->getAttribute($attrCode)->getId();
                            }
                        }
                    }
                }
            }
        }
        return $params;
    }

    public function getAttributeIdBySuperAttribute($superAttributeId)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = 'SELECT attribute_id FROM ' . $resource->getTableName('catalog_product_super_attribute') . ' WHERE product_super_attribute_id = ' . $superAttributeId;
        $result = $readConnection->fetchOne($query);
        return $result;
    }

    /**
     * Recalculating price of product which has configurable parent and set price adjustment
     * Returns product price
     * @param $product
     * @param $groupId
     * @param $params
     */
    public function getProductGroupPrice($product, $groupId, $params)
    {
        if ($product->getTypeId() == 'simple') {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                $parent = Mage::getModel('catalog/product')->setStoreId($this->storeVersionId)->load($parentIds[0]);
                $tempPrice = ($this->getGroupPrice($parent, $groupId)) ? $this->getGroupPrice($parent, $groupId) : $parent->getPrice();
                if ($tempPrice) {
                    $parent->getTypeInstance(true)
                        ->setStoreFilter($parent->getStore(), $parent);
                    $attributes = $parent->getTypeInstance(true)
                        ->getConfigurableAttributes($parent);
                    $add = 0;
                    foreach ($attributes as $attribute) {
                        $prices = $attribute->getPrices();
                        $add += $this->getConfigurableProductWithParametersDifference($tempPrice, $prices, $params);
                    }
                    return $tempPrice + $add;
                }
            }
        }
        $groupPrice = $this->getGroupPrice($product, $groupId);
        if ($groupPrice) {
            return $groupPrice;
        }
        $price = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
        if ($price) {
            return $price;
        }
        return $product->getPrice();
    }

    /**
     *
     * @param $basePrice
     * @param $prices
     * @param $params
     * @return float|int price difference
     */
    public function getConfigurableProductWithParametersDifference($basePrice, $prices, $params)
    {
        $out = 0;
        $temp = array();
        if (!empty($prices)) {
            foreach ($prices as $price) {
                if ($superAttrId = $this->getAttributeIdBySuperAttribute($price['product_super_attribute_id'])) {
                    foreach ($params as $param) {
                        $attrId = $param['id'];
                        if ($attrId == $superAttrId && $param['key'] == $price['label']) {
                            $temp = $price;
                            break;
                        }
                    }
                }
                if (!empty($temp)) {
                    break;
                }
            }
        }
        if (!empty($temp)) {
            if (isset($temp['is_percent']) && $temp['is_percent'] == '1' && isset($temp['pricing_value'])) {
                $out = $basePrice * $temp['pricing_value'] / 100;
            }
        }
        $temp = null;
        return $out;
    }

    /**
     * Return group price of product
     * @param $product
     * @param $groupId
     */
    public function getGroupPrice($product, $groupId)
    {
        $groupPrice = $product->getData('group_price');
        if (isset($groupPrice[$groupId]['price'])) {
            $customerGroupPrice = $groupPrice[$groupId]['price'];
            if ($product->getFinalPrice() < $customerGroupPrice) {
                return $product->getFinalPrice();
            }
            return $customerGroupPrice;
        }
        return;
    }

    /**
     * If product is simple and has configurable parent it returns product's parent description (full or short)
     * @param $product
     * @param bool $full full or shor description
     * @return mixed|string
     */
    public function getParentDescription($product, $full = false)
    {
        if ($product->getTypeId() == 'simple') {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                if ($full) {
                    return $this->prepareFullDescription($parent->getDescription());
                }
                return htmlspecialchars($parent->getShortDescription());
            }
        }
        if ($full) {
            return $this->prepareFullDescription($product->getDescription());
        }
        return htmlspecialchars($product->getShortDescription());
    }

    /**
     * Preparing product collection
     * Saving collection in the file
     * @param int $customerGroup
     */
    public function prepareProductCollection()
    {
        $helper = Mage::helper('bussinessfeed');
        $baseMediaUrl = rtrim(Mage::app()->getStore($this->storeVersionId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA), '/');
        $preparedData = array();
        $products = $this->getProductCollection();
        foreach ($products as $key => $product) {
            $product = $this->loadProduct($product);
            // get array of params
            $params = $this->getParametersAssignedToConfigurableProduct($product);
            $preparedData[$key]['description'] = $this->getParentDescription($product);
            if ($this->fullDescription) {
                $preparedData[$key]['description_full'] = $this->getParentDescription($product, true);
            }
            if ($this->includePrices) {
                // get group price of product
                $price = $this->getProductGroupPrice($product, $this->groupId, $params);
                $preparedData[$key]['price'] = $price;
                $preparedData[$key]['price_vat'] = $this->getVatPrice($price);
                if ($this->extraPrice) {
                    // get extra price
                    $extraPrice = $this->getProductGroupPrice($product, $this->extraPrice, $params);
                    $preparedData[$key]['price_general'] = $extraPrice;
                    $preparedData[$key]['price_general_vat'] = $this->getVatPrice($extraPrice);
                }
            }
            $preparedData[$key]['imgurl'] = $baseMediaUrl . '/catalog/product' . $product->getImage();
            $preparedData[$key]['vat'] = $this->getVat();

            $preparedData[$key]['product'] = $product->getName();
            $preparedData[$key]['item_id'] = $product->getSku();
            $preparedData[$key]['params'] = $this->rebuildParams($params);
            $preparedData[$key]['categorytext'] = $this->getCategoryName($product);
            $preparedData[$key]['manufacturer'] = $product->getAttributeText('manufacturer');
            $preparedData[$key]['ean'] = $product->getEan();
            $preparedData[$key]['delivery_date'] = $product->getAttributeText('availability');
            $product->clearInstance();
        }
        $this->appendData($helper->prepareXmlShopItem($preparedData));
        $preparedData = null;
        $products = null;
    }

    /**
     * Removing all img tags from description
     * @param $description
     * @return mixed|string
     */
    public function prepareFullDescription($description) {
        $description = preg_replace("/<img[^>]+\>/i", "", $description);
        $description = '<![CDATA[' . $description . ']]>';
        return $description;
    }

    /**
     * @param $params
     * @return array
     */
    public function rebuildParams($params) {
        $out = array();
        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $out[$key] = $param['key'];
            }
        }
        return $out;
    }

    /**
     * @param $price
     * @return string
     */
    public function getVatPrice($price)
    {
        $vat = $this->getVat();
        $vatPrice = $price * $vat + $price;
        return number_format($vatPrice, 2);
    }

    /**
     * Retrieves attribute by its id
     * @param $attributeId
     * @return array|mixed|void
     */
    public function getAttributeById($attributeId)
    {
        // attribute was already retrieved from database
        if (array_key_exists($attributeId, $this->getParams())) {
            return $this->getParams($attributeId);
        }
        // retrieve attribute from database
        $attribute = Mage::getModel('eav/entity_attribute')
            ->load($attributeId);
        if ($attribute) {
            // append retrieved param to array
            return $this->addParam($attributeId, $attribute)->getParams($attributeId);
        }
        return;
    }

    /**
     * Retrieves product's category name
     * @param $product
     * @return string
     */
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

    protected $_product;

    public function loadProduct($id)
    {
        $product = Mage::getModel('catalog/product')->setStoreId($this->storeVersionId)->load($id);
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
