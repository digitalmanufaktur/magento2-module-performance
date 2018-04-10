<?php
declare(strict_types=1);

/**
 * Cache helper
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Helper;

use DMC\Base\Helper\Data;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\Context;

class Cache extends Data
{

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Cache constructor.
     *
     * @param Context        $context
     * @param CacheInterface $cache
     */
    public function __construct(Context $context, CacheInterface $cache)
    {
        parent::__construct($context);
        $this->cache = $cache;
    }

    /**
     *
     * Fetch cached data by attribute
     *
     * @param string $cacheKey
     *
     * @return array|null
     */
    public function getCachedData(string $cacheKey)
    {
        $data = $this->cache->load($cacheKey);
        if ($data) {
            return json_decode($data, true);
        }

        return null;
    }

    /**
     * Set up new data cache
     *
     * @param string $cacheKey
     * @param array  $cachedData
     * @param array  $cacheTags
     *
     * @return bool
     */
    public function setCachedData(string $cacheKey, array $cachedData, array $cacheTags): bool
    {
        return $this->cache->save(json_encode($cachedData), $cacheKey, $cacheTags);
    }

}