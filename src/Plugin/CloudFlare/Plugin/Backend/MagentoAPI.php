<?php
declare(strict_types=1);

/**
 * Fix cloudflare domain check
 *
 * @category    DMC
 * @package     DMC_Performance
 * @author      digital.manufaktur GmbH / Hannover, Germany
 */

namespace DMC\Performance\Plugin\CloudFlare\Plugin\Backend;

use CloudFlare\Plugin\Backend\MagentoAPI as MagentoAPISubject;

class MagentoAPI
{

    /**
     * Fix magento domain retrieval
     *
     * @param MagentoAPISubject $subject
     * @param callable          $proceed
     *
     * @see MagentoAPISubject::getMagentoDomainName()
     * @return string
     */
    public function aroundGetMagentoDomainName(MagentoAPISubject $subject, callable $proceed): string
    {
        $domain = $proceed();

        // Strip suffix name like /admin from domain
        if (strpos($domain, '/') !== false) {
            return dirname($domain);
        }
        return $domain;
    }
}