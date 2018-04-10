<?php
declare(strict_types=1);

/**
 * Enable CMS block caching by demand
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Plugin\Magento\Cms\Block;

use Magento\Cms\Block\Block as BlockSubject;
use Magento\Store\Model\StoreManagerInterface;

class Block
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Block constructor.
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Get cache key informative items
     *
     * Provide string array key to share specific info item with FPC placeholder
     *
     * @param BlockSubject $subject
     * @param callable     $proceed
     *
     * @return string[]
     */
    public function aroundGetCacheKeyInfo(BlockSubject $subject, callable $proceed): array
    {
        $info = $proceed();
        if ($subject->getCacheLifetime() && $subject->getBlockId()) {
            $info['store_id'] = $this->storeManager->getStore()->getId();
            $info['block_id'] = $subject->getBlockId();
        }

        // To use the cms block cache, attach a cache_lifetime to the block's data by param or injection
        return $info;
    }
}