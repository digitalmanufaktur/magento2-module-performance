<?php
declare(strict_types=1);

/**
 * Cache attribute to speed up loading time
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Plugin\Magento\Eav\Model\Entity\Attribute\Source;

use DMC\Performance\Helper\Cache;
use Magento\Eav\Model\Cache\Type;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\Table as TableSubject;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Table
{

    const CACHE_PREFIX = 'E_A_';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Cache
     */
    protected $cacheHelper;

    /**
     * Table constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Cache                 $cacheHelper
     */
    public function __construct(StoreManagerInterface $storeManager, Cache $cacheHelper)
    {
        $this->storeManager = $storeManager;
        $this->cacheHelper = $cacheHelper;
    }

    /**
     * Wrap around the original method to fetch to check cache and fetch data
     *
     * @TODO DMC use for isAttributeCachingEnabled
     * @param TableSubject $subject
     * @param callable     $proceed
     *
     * @param              $ids
     * @param bool         $withEmpty
     *
     * @return array
     */
    public function aroundGetSpecificOptions(TableSubject $subject, callable $proceed, $ids, $withEmpty = true): array
    {
        $cacheKey = static::CACHE_PREFIX . $subject->getAttribute()->getId() . '_' . $this->storeManager->getStore()->getId();

        $cachedData = $this->cacheHelper->getCachedData($cacheKey);
        if ($cachedData === null) {
            $cachedData = $subject->getAllOptions(false);
            $this->cacheHelper->setCachedData($cacheKey, $cachedData, [Store::CACHE_TAG, Type::CACHE_TAG, Attribute::CACHE_TAG, Attribute::CACHE_TAG . '_' . $subject->getAttribute()->getId()]);
        }

        $options = $cachedData;

        // Do the filtering
        if (!$ids) {
            return $options;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $foundOptions = [];
        if ($withEmpty) {
            array_unshift($foundOptions, ['label' => $subject->getAttribute()->getIsRequired() ? '' : ' ', 'value' => '']);
        }

        foreach ($options as $option) {
            if (in_array($option['value'], $ids)) {
                $foundOptions[] = $option;
            }
        }

        return $foundOptions;
    }

}