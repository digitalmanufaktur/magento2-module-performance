<?php
declare(strict_types=1);

/**
 * Theme adjustment for performance improvement
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Magento\Theme\Model\Theme;

use Magento\Theme\Model\Theme\ThemeProvider as ThemeProviderOrigin;

class ThemeProvider extends ThemeProviderOrigin
{

    /**
     * @var \Magento\Framework\View\Design\ThemeInterface[]
     */
    private $themes;

    /**
     * @inheritdoc
     */
    public function getThemeByFullPath($fullPath)
    {
        // Always cache full path, even if it does not exist
        if (isset($this->themes[$fullPath])) {
            return $this->themes[$fullPath];
        }

        $theme = parent::getThemeByFullPath($fullPath);
        $this->themes[$fullPath] = $theme;
        return $theme;

    }

}