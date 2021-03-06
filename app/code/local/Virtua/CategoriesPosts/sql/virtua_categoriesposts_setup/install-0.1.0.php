<?php
/**
 * Removing recent blog posts section from cms block
 */
$installer = $this;
$installer->startSetup();
$filter = '<div id="latest_news" style="margin: 0 -10px;">{{block type="blog/last" name="latest_news" template="blog/recentposts_home.phtml"}}</div>';
$cmsBlocksModel = Mage::getModel('cms/block');
$cmsBlocks = $cmsBlocksModel
    ->getCollection()
    ->addFieldToFilter('content', array('like' => '%'.$filter.'%'));

if ($cmsBlocks->getSize() > 0) {
    foreach ($cmsBlocks as $cmsBlock) {
        $data = [
            'title' => $cmsBlock->getTitle().'_old',
            'identifier' => $cmsBlock->getIdentifier().'_old',
            'content' => $cmsBlock->getContent(),
            'is_active' => 0,
        ];

        $cmsBlocksModel->setData($data)->save();

        /**
         * Gets current block store id and saves it(else store id will be null).
         */
        $storeId = $cmsBlock->getResource()->lookupStoreIds($cmsBlock->getBlockId());
        $cmsBlock
            ->setStores($storeId)
            /**
             * Set new content with replaced filtered text to empty string
             */
            ->setContent(str_replace($filter, '', $cmsBlock->getContent()))
            ->save();
    }
}

$installer->endSetup();
