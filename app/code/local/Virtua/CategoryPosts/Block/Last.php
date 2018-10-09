<?php

/**
 * Class Virtua_CategoryPosts_Block_Last
 */
class Virtua_CategoryPosts_Block_Last extends Smartwave_Blog_Block_Last implements Mage_Widget_Block_Interface
{
    /**
     * Modified Smartwave/Blog function.
     * @return array
     */
    public function getRecent()
    {
        if (!Mage::registry('current_category')) {
            return parent::getRecent();
        }
        $collection = $this->getBlogCollection();
        $collection->addFieldToFilter('tags', $this->getTagsFromUrl())->getSelect()->limit(6);
        $size = $collection->getSize();

        if ($size == 0) {
            /** If there are 0 category posts, selects last recent*/
            $collection = $this->getBlogCollection();
            $collection->getSelect()->limit(6);
        } elseif ($size < 6) {
            /** If there are not 6 category posts, selects the next ones */
            $mergedIds = array_merge($collection->getAllIds(), $this->getRecentPostsIds($size, $collection->getAllIds()));
            $collection = $this->getBlogCollection();
            $collection
                ->addFilterToMap('post_id', 'main_table.post_id')
                ->addFieldToFilter('post_id', $mergedIds);
        }

        if ($collection && $this->getData('categories')) {
            $collection->addCatsFilter($this->getData('categories'));
        }
        foreach ($collection as $item) {
            $item->setAddress($this->getBlogUrl($item->getIdentifier()));
        }
        return $collection;
    }

    /**
     * Gets blog collection
     * @return array
     */
    public function getBlogCollection()
    {
        $collection = Mage::getModel('blog/blog')->getCollection()
            ->addPresentFilter()
            ->addEnableFilter(Smartwave_Blog_Model_Status::STATUS_ENABLED)
            ->addStoreFilter()
            ->setOrder('created_time', 'desc');

        return $collection;
    }

    /**
     * Gets tags from category url
     * @return array
     */
    public function getTagsFromUrl()
    {
        $currentUrl = explode('.', Mage::registry('current_category')->getData('url_path'));
        if (array_key_exists(0, $currentUrl)) {
            $currentUrl = $currentUrl[0];
        }
        $currentUrl = str_replace('-', ' ', $currentUrl);
        $tags = explode('/',$currentUrl);
        $tags = array_reverse($tags);

        foreach ($tags as $tag) {
            $queries[] = array('like' => '%'.$tag.'%');
        }

        return $queries;
    }

    /**
     * Gets some recent posts if there are less than 6 posts in category
     * @param $size
     * @param array|null $tagIds
     * @return array
     */
    public function getRecentPostsIds($size, $tagIds = null)
    {
        $numberOfPosts = 6 - $size;
        $collection = $this->getBlogCollection();
        $collection->getSelect()->limit($numberOfPosts);

        foreach ($collection as $recentPost) {
            $id = $recentPost->getId();
            if (!in_array($id, $tagIds)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}
