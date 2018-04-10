<?php
declare(strict_types=1);

/**
 * Catalog helper
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Helper;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\CatalogRule\Pricing\Price\CatalogRulePrice;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class Catalog extends \DMC\Base\Helper\Catalog
{

    const COLLECTION_FLAG_HAS_PRELOAD_DATA = 'has_preload_data';

    /**
     * @var StockItemRepositoryInterface
     */
    protected $stockItemRepository;

    /**
     * @var StockItemCriteriaInterface
     */
    protected $stockItemCriteria;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Catalog constructor.
     *
     * @param Context                      $context
     * @param ResourceConnection           $resourceConnection
     * @param StockItemCriteriaInterface   $stockItemCriteria
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param Rule                         $rule
     * @param TimezoneInterface            $dateTime
     * @param Session                      $customerSession
     * @param StoreManagerInterface        $storeManager
     */
    public function __construct(Context $context,
                                ResourceConnection $resourceConnection,
                                StockItemCriteriaInterface $stockItemCriteria,
                                StockItemRepositoryInterface $stockItemRepository,
                                Rule $rule,
                                TimezoneInterface $dateTime,
                                Session $customerSession,
                                StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context, $resourceConnection);
        $this->resourceConnection = $resourceConnection;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockItemCriteria = $stockItemCriteria;
        $this->rule = $rule;
        $this->dateTime = $dateTime;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Prenote the samples by listing IDs
     *
     * @param Collection $collection
     */
    public function attachPreloadDataToCollection(Collection $collection)
    {
        if (!$collection->hasFlag(static::COLLECTION_FLAG_HAS_PRELOAD_DATA)) {
            $collection->setFlag(static::COLLECTION_FLAG_HAS_PRELOAD_DATA, true);

            // Add data to prevent single queries later for each item
            $collection->addTierPriceData();
            $this->addStockItemsToCollection($collection);
            $this->addCatalogRulePricesToCollection($collection);
            $collection->addCategoryIds();

            // @TODO DMC add websiteids?
        }
    }

    /**
     * Add catalog rule prices to collection
     *
     * @param Collection $productCollection
     *
     * @return void
     */
    protected function addCatalogRulePricesToCollection(Collection $productCollection)
    {
        $productIds = $productCollection->getColumnValues('entity_id');

        $prices = $this->rule->getRulePrices(
            $this->dateTime->scopeDate($this->storeManager->getStore()->getId()),
            $this->storeManager->getStore()->getWebsiteId(),
            $this->customerSession->getCustomerGroupId(),
            $productIds
        );

        foreach ($productCollection->getItems() as $productId => $product) {

            if (!isset($prices[$productId])) {
                $product->setData(CatalogRulePrice::PRICE_CODE, null);
                continue;
            }

            $product->setData(CatalogRulePrice::PRICE_CODE, $prices[$productId]);
        }
    }

    /**
     * Add stock status to products
     *
     * @param Collection $productCollection
     *
     * @return mixed
     */
    public function addStockItemsToCollection(Collection $productCollection)
    {
        $productIds = $productCollection->getColumnValues('entity_id');

        $criteria = clone $this->stockItemCriteria;
        $criteria->setProductsFilter($productIds);
        $criteria->setScopeFilter(0);
        $stockCollection = $this->stockItemRepository->getList($criteria);
        $stockStatus = $stockCollection->getItems();
        foreach ($stockStatus as $stockItem) {

            $productItem = $productCollection->getItemById($stockItem->getProductId());
            if (!$productItem || !$productItem->getId()) {
                continue;
            }

            $productItem->setStockItem($stockItem);
        }
    }

}