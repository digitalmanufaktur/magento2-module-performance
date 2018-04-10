<?php
declare(strict_types=1);

/**
 * Refined Image Resize command
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Console\Command;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\EntityManager\MetadataPool;

class ImagesResizeCommand extends \Magento\Catalog\Console\Command\ImagesResizeCommand
{

    const COLLECTION_PAGE_SIZE = 1000;

    /**
     * @var MetadataPool $metadataPool
     */
    protected $metadataPool;

    /**
     * @var string $productEntityLinkField
     */
    protected $productEntityLinkField;

    /**
     * @var ResourceConnection $resourceConnection
     */
    protected $resourceConnection;

    /**
     * @var DataObject\Factory
     */
    protected $dataObjectFactory;

    /**
     * ImagesResizeCommand constructor.
     *
     * @param \Magento\Framework\App\State                                   $appState
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                $productRepository
     * @param \Magento\Catalog\Model\Product\Image\CacheFactory              $imageCacheFactory
     * @param ResourceConnection                                             $resourceConnection
     * @param MetadataPool                                                   $metadataPool
     *
     * @param DataObject\Factory                                             $dataObjectFactory
     *
     * @throws \Exception
     */
    public function __construct(\Magento\Framework\App\State $appState, \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Magento\Catalog\Model\Product\Image\CacheFactory $imageCacheFactory, ResourceConnection $resourceConnection, MetadataPool $metadataPool, DataObject\Factory $dataObjectFactory)
    {
        parent::__construct($appState, $productCollectionFactory, $productRepository, $imageCacheFactory);
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->productEntityLinkField = $this->metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('dmc:catalog:images:resize')
            ->setDescription('Creates resized product images, optimized for speed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    )
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        /** @var Collection $productCollection */
        $productCollection = $this->productCollectionFactory->create();

        // Require all 3 attributes to be set
        $productCollection->addAttributeToFilter('image', ['notnull' => true]);
        $productCollection->addAttributeToFilter('small_image', ['notnull' => true]);
        $productCollection->addAttributeToFilter('thumbnail', ['notnull' => true]);

        $totalRecords = $productCollection->getSize();
        if (!count($totalRecords)) {
            $output->writeln("<info>No product images to resize</info>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        try {
            // Load in bulks
            $pages = ceil($totalRecords / static::COLLECTION_PAGE_SIZE);
            $count = 1;
            /** @var \Magento\Catalog\Model\Product\Image\Cache $imageCache */
            $imageCache = $this->imageCacheFactory->create();
            for ($currentPage = 1; $currentPage < ($pages+1); $currentPage++) {
                $pageProductCollection = clone $productCollection;

                $pageProductCollection->resetData();
                $pageProductCollection->setPage($currentPage, static::COLLECTION_PAGE_SIZE);
                $productIds = $pageProductCollection->getAllIds(static::COLLECTION_PAGE_SIZE, (static::COLLECTION_PAGE_SIZE * ($currentPage - 1)));
                $mediaGalleryData = $this->getMediaGalleryData($productIds);
                $memoryConsumption = (memory_get_usage(true) / 1024 / 1024);
                foreach ($productIds as $productId) {
                    $output->write('Current product: ' . $count++ . '/' . $totalRecords . ' - memory consumption: ' . $memoryConsumption . "MB \r");
                    try {
                        if (!isset($mediaGalleryData[$productId])) {
                            continue;
                        }
                        /** @var \Magento\Catalog\Model\Product $product */
                        $product = $pageProductCollection->getItemById($productId);
                        if (!$product) {
                            continue;
                        }

                        $product->setMediaGalleryImages($mediaGalleryData[$productId]);
                        $this->setImageObjectsToProduct($product);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        continue;
                    }

                    $imageCache->generate($product->setGenerateFlaggedImages(true));

                }
            }
            $output->write(PHP_EOL . PHP_EOL);

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->write("\n");
        $output->writeln("<info>Product images resized successfully</info>");

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * Get image gallery for product collection
     *
     * @param array $productIds
     *
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getMediaGalleryData(array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        if (!$productIds) {
            return [];
        }

        $select = $connection->select()->from(
            ['mgvte' => $connection->getTableName('catalog_product_entity_media_gallery_value_to_entity')],
            [
                "mgvte.{$this->productEntityLinkField}",
                'mgvte.value_id',
            ]
        )->joinLeft(
            ['mg' => $connection->getTableName('catalog_product_entity_media_gallery')],
            '(mg.value_id = mgvte.value_id)',
            [
                'mg.attribute_id',
                'filename' => 'mg.value',
            ]
        )->joinLeft(
            ['mgv' => $connection->getTableName('catalog_product_entity_media_gallery_value')],
            '(mg.value_id = mgv.value_id AND mgv.store_id = 0)',
            [
                'mgv.label',
                'mgv.position',
                'mgv.disabled',
            ]
        )->where(
            "mgvte.{$this->productEntityLinkField} IN (?)",
            $productIds
        )->distinct(true);

        $rowMediaGallery = [];
        $stmt = $connection->query($select);
        while ($mediaRow = $stmt->fetch()) {
            // Emulate the object so we can use this later
            $imageObject = $this->dataObjectFactory->create();
            $imageObject->addData([
                'file' => $mediaRow['filename'],
            ]);
            $rowMediaGallery[$mediaRow[$this->productEntityLinkField]][] = $imageObject;
        }
        return $rowMediaGallery;
    }

    /**
     * Wrap image objects around the attribute values as a special index
     *
     * @param ProductInterface $product
     */
    protected function setImageObjectsToProduct(ProductInterface $product)
    {
        $productImages = [];
        foreach (['image', 'small_image', 'thumbnail'] as $imageType) {
            if ($product->getData($imageType)) {
                $imageObject = $this->dataObjectFactory->create([
                    'file' => $product->getData($imageType),
                    'type' => $imageType,
                ]);
                $productImages[] = $imageObject;
            }
        }
        $product->setImageObjects($productImages);
    }

}
