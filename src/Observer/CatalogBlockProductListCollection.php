<?php
declare(strict_types=1);

/**
 * Fetch collection data
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Observer;

use DMC\Performance\Helper\Catalog;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogBlockProductListCollection implements ObserverInterface
{

    /**
     * @var Catalog
     */
    protected $helper;

    /**
     * CatalogBlockProductListCollection constructor.
     *
     * @param Catalog $helper
     */
    public function __construct(Catalog $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Make the product collection known
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var  Collection $collection */
        $collection = $observer->getCollection();
        $this->helper->attachPreloadDataToCollection($collection);
    }

}