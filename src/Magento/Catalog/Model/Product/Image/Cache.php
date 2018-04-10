<?php
declare(strict_types=1);

/**
 * Improved cache helper
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Magento\Catalog\Model\Product\Image;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\Cache as CacheOrigin;
use Magento\Framework\App\Area;

class Cache extends CacheOrigin
{

    /**
     * Only used image types will be generated this way using filtered IDs
     *
     * @var array $generateData
     */
    protected $generateData = [];

    /**
     * All given IDs will be generated for these
     *
     * @var array $generateGalleryData
     */
    protected $generateGalleryData = [];

    /**
     * Resize product images and save results to image cache
     *
     * @param Product $product
     *
     * @return $this
     */
    public function generate(Product $product)
    {
        if (!$product->getGenerateFlaggedImages()) {
            return parent::generate($product);
        }

        return $this->generateImages($product);
    }

    /**
     * Generate flagged data only
     *
     * @param Product $product
     *
     * @return Cache
     */
    protected function generateImages(Product $product)
    {
        $productImages = $product->getImageObjects();
        foreach ($productImages as $image) {
            foreach ($this->getGenerateData() as $imageData) {
                if ($imageData['type'] !== $image['type']) {
                    continue;
                }
                $this->processImageData($product, $imageData, $image->getFile());
            }
        }

        $galleryImages = $product->getMediaGalleryImages();
        if ($galleryImages) {
            foreach ($galleryImages as $image) {
                foreach ($this->getGenerateGalleryData() as $imageData) {
                    $this->processImageData($product, $imageData, $image->getFile());
                }
            }
        }
        return $this;
    }

    /**
     * Get only flagged image types
     *
     * @return array
     */
    protected function getGenerateData(): array
    {
        if ($this->generateData) {
            return $this->generateData;
        }

        $generateData = [];
        /** @var \Magento\Theme\Model\Theme $theme */
        foreach ($this->themeCollection->loadRegisteredThemes() as $theme) {
            $config = $this->viewConfig->getViewConfig([
                'area'       => Area::AREA_FRONTEND,
                'themeModel' => $theme,
            ]);

            // We only allow defined data within the same theme, copy data to the theme if you want to use it!
            $allowedThemeImageIds = $config->getVarValue('Magento_Catalog', 'generate_image_ids');
            if (!$allowedThemeImageIds || !is_array($allowedThemeImageIds)) {
                continue;
            }
            $themeImages = $config->getMediaEntities('Magento_Catalog', ImageHelper::MEDIA_TYPE_CONFIG_NODE);
            $images = array_intersect_key($themeImages, $allowedThemeImageIds);
            foreach ($images as $imageId => $imageData) {
                $generateData[$theme->getCode() . $imageId] = array_merge(['id' => $imageId], $imageData);
            }
        }

        $this->generateData = $generateData;

        return $generateData;
    }

    /**
     * Get only flagged image types
     *
     * @return array
     */
    protected function getGenerateGalleryData(): array
    {
        if ($this->generateGalleryData) {
            return $this->generateGalleryData;
        }

        $generateGalleryData = [];
        /** @var \Magento\Theme\Model\Theme $theme */
        foreach ($this->themeCollection->loadRegisteredThemes() as $theme) {
            $config = $this->viewConfig->getViewConfig([
                'area'       => Area::AREA_FRONTEND,
                'themeModel' => $theme,
            ]);

            // We only allow defined data within the same theme, copy data to the theme if you want to use it!
            $allowedThemeImageIds = $config->getVarValue('Magento_Catalog', 'generate_gallery_image_ids');
            if (!$allowedThemeImageIds || !is_array($allowedThemeImageIds)) {
                continue;
            }
            $themeImages = $config->getMediaEntities('Magento_Catalog', ImageHelper::MEDIA_TYPE_CONFIG_NODE);
            $images = array_intersect_key($themeImages, $allowedThemeImageIds);
            foreach ($images as $imageId => $imageData) {
                $generateGalleryData[$theme->getCode() . $imageId] = array_merge(['id' => $imageId], $imageData);
            }
        }

        $this->generateGalleryData = $generateGalleryData;

        return $generateGalleryData;
    }
}