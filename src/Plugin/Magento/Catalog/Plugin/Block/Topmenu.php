<?php
declare(strict_types=1);

/**
 * Cache topmenu data
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Plugin\Magento\Catalog\Plugin\Block;

use DMC\Performance\Helper\Cache;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

class Topmenu extends \Magento\Catalog\Plugin\Block\Topmenu
{

    const CACHE_PREFIX = 'B_TM_ID_';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection\Factory
     */
    protected $collectionFactory;

    /**
     * @var Collection
     */
    protected $dataCollection;

    /**
     * @var CategoryInterface
     */
    protected $category;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;

    /**
     * @var Cache
     */
    protected $cacheHelper;

    /**
     * Topmenu constructor.
     *
     * @param \Magento\Catalog\Helper\Category                                $catalogCategory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface                      $storeManager
     * @param \Magento\Catalog\Model\Layer\Resolver                           $layerResolver
     * @param Cache                                                           $cacheHelper
     * @param Collection                                                      $dataCollection
     * @param CategoryInterface                                               $category
     */
    public function __construct(\Magento\Catalog\Helper\Category $catalogCategory, \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Catalog\Model\Layer\Resolver $layerResolver, Cache $cacheHelper, Collection $dataCollection, CategoryInterface $category)
    {
        parent::__construct($catalogCategory, $categoryCollectionFactory, $storeManager, $layerResolver);
        $this->catalogCategory = $catalogCategory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->layerResolver = $layerResolver;
        $this->dataCollection = $dataCollection;
        $this->category = $category;
        $this->cacheHelper = $cacheHelper;
    }

    /**
     * Get Category Tree
     * - cached with emulated collection and object
     *
     * @param int $storeId
     * @param int $rootId
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Exception
     * @throws LocalizedException
     */
    protected function getCategoryTree($storeId, $rootId)
    {
        $collectionArray = $this->getCacheData($storeId, $rootId);

        $dataCollection = clone $this->dataCollection;
        foreach ($collectionArray as $item) {
            $category = clone $this->category;
            $category->setData($item);
            $dataCollection->addItem($category);
        }

        return $dataCollection;
    }

    /**
     * Return cached data, renew if required
     *
     * @param $storeId
     * @param $rootId
     *
     * @return array
     * @throws LocalizedException
     */
    protected function getCacheData($storeId, $rootId): array
    {
        $cacheKey = static::CACHE_PREFIX . $storeId . '_' . $rootId;
        $cachedData = $this->cacheHelper->getCachedData($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $collection = parent::getCategoryTree($storeId, $rootId);
        $items = $collection->getItems();

        $cacheTags = [
            Category::CACHE_TAG,
            Category::CACHE_TAG . '_' . $rootId,
            Store::CACHE_TAG,
            Store::CACHE_TAG . '_' . $storeId,
        ];
        foreach ($items as $category) {
            $cachedData[$category->getId()] = $category->getData();
            $cacheTags[] = Category::CACHE_TAG . '_' . $category->getId();
        }

        $this->cacheHelper->setCachedData($cacheKey, $cachedData, $cacheTags);
        return $cachedData;

    }

}